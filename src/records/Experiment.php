<?php

namespace matfish\Optimum\records;


use Carbon\Carbon;
use Craft;

class Experiment extends \craft\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%optimum_experiments}}';
    }

    public function getVariants(): \craft\db\ActiveQuery
    {
        return $this->hasMany(Variant::class, ['experimentId' => 'id']);
    }

    public function isActive(): bool
    {
        return $this->enabled &&
            (!$this->startAt || Carbon::parse($this->startAt) <= Carbon::now()) &&
            (!$this->endAt || Carbon::parse($this->endAt) >= Carbon::now());
    }

    private function getRandomWeightedNumber(array $weights): int|string
    {
        $rand = random_int(1, (int)array_sum($weights));

        foreach ($weights as $key => $value) {
            $rand -= $value;
            if ($rand <= 0) {
                return $key;
            }
        }

        throw new \Exception("Optimum: Failed to randomize element");
    }

    public function getRandomWeightedElement(): string
    {
        $variants = $this->getVariants()->all();
        $vars = [];
        foreach ($variants as $variant) {
            $vars[$variant->handle] = $variant->weight;
        }

        return $this->getRandomWeightedNumber($vars);
    }

    public function getOrSetExperimentCookie(): string|null
    {
        $testVar = Craft::$app->request->getParam('optimum');
        $key = "optimum_{$this->handle}";

        if ($testVar) {
            $variants = array_map(function ($item) {
                return $item->handle;
            }, $this->getVariants()->all());

            if (in_array($testVar, $variants, true)) {
                return $testVar;
            }
        }

        $value = $this->getCookieValue();
        if ($value) {
            return $value['variant'];
        }

        $shouldInclude = $this->shouldIncludeInExperiment();
        $randomVariant = $shouldInclude ? $this->getRandomWeightedElement() : null;

        // Create cookie object with both variant and inclusion status
        $cookie = Craft::createObject([
            'class' => \yii\web\Cookie::class,
            'name' => $key,
            'httpOnly' => true,
            'value' => json_encode([
                'variant' => $randomVariant,
                'included' => $shouldInclude
            ]),
            'expire' => Carbon::parse($this->endAt)->format('U'),
        ]);

        Craft::$app->getResponse()->getCookies()->add($cookie);

        return $randomVariant;
    }

    public function isIncludedInExperiment(): bool
    {
        $value = $this->getCookieValue();
        return $value ? (bool)$value['included'] : false;
    }

    public function shouldIncludeInExperiment(): bool
    {
        $weights = [
            1 => $this->populationSegment,
            0 => 100 - $this->populationSegment
        ];

        return (bool)$this->getRandomWeightedNumber($weights);
    }

    private function getCookieValue(): ?array
    {
        try {
            $key = "optimum_{$this->handle}";
            
            // First check request cookies
            $cookie = \Craft::$app->request->cookies->get($key);

            if (!$cookie) {
                // If not in request, check response cookies
                $cookie = \Craft::$app->response->cookies->get($key);
                if (!$cookie) {
                    return null;
                }
            }

            // Handle legacy cookies that only contain variant string
            if (!str_starts_with($cookie->value, '{')) {
                return [
                    'variant' => $cookie->value,
                    'included' => true // Legacy cookies were always included
                ];
            }

            $decoded = json_decode($cookie->value, true);
            if (!is_array($decoded) || !isset($decoded['variant'])) {
                Craft::warning("Optimum: Malformed cookie value for experiment {$this->handle}", __METHOD__);
                return [
                    'variant' => null,
                    'included' => false
                ];
            }

            return $decoded;
        } catch (\Throwable $e) {
            Craft::error("Optimum: Error reading cookie for experiment {$this->handle}: " . $e->getMessage(), __METHOD__);
            return [
                'variant' => null,
                'included' => false
            ];
        }
    }
}
