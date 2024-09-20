## Craft Optimum

> **_IMPORTANT:_** Version 1.5 introduces a breaking change. If you upgrade to this version, you will need to [call `fireEvent`](#3-fire-the-event) explicitly in your twig template.

This plugin allows the user to conduct server-side A/B testing in CraftCMS.
As opposed to client-side testing (e.g using Google Optimize), the test variant is rendered on the server-side, resulting in better UX, performance and enhanced flexibility.

Once an experiment is set the data is automatically sent to Google Analytics 4 as a [custom dimension](https://support.google.com/analytics/answer/10075209).
You then have the full power of analytics to compare the test groups over different metrics (e.g conversion, engagement etc.)

If you are using a different analytics platform, you can still use the plugin to conduct the test, but you will need to manually set up the tracking.

### Requirements

1. Craft CMS 4.x or later.
If using GA to track:
2. A Google Analytics 4 (GA4) Account. 
3. Google Tag Manager script installed on the page (type `gtag` in the browser console to verify).

> **_NOTE:_** While the last two points are not crucial for development, for the sake of completion it is recommended to create a dummy `gtag` [function](#local-development) 
### Installation

1. Include the package:

```
composer require matfish/craft-optimum
```

2. Install the plugin:

```
php craft plugin/install optimum
```
### Usage

#### 1. Create an experiment in the control panel:
Click on the new "Experiments" menu item and then click on "New Experiment".
An experiment consists of the following fields:
- **Name** E.g 'Banner Types'
- **Handle** - E.g `bannerTypes`. This will be used in two places:
  1. In naming the folder for the variants' templates.
  2. As the param name, when setting up GA4's custom dimension 
- **Enabled?** - Whether the experiment is currently active. This can be used to pause or permanently stop the experiment
- **Variants** - The different variants for the experiment. E.g if you are testing different hero banners you would set their reference here. An "original" variant is pre-set and refers to the control group, represented by your current code. Its handle cannot be modified, and it cannot be deleted.
    Each variant comprises three fields:
  - Name: Human readable name. This will be sent to GA4 as the value (E.g "Wide Banner")
  - Handle: Used for naming the variant template in twig (E.g "wideBanner")
  - Weight: Relative weight (probability) in percents (e.g 40). The sum of all variants' weight must add up to 100%
- **Starts at**: Optional field to defer the experiment. If left empty experiment starts immediately (assuming that "Enabled?" is on).
- **Ends at**: Set to 30 days in the future by default.
#### 2. Create the variants in twig
#####  Method A: Polymorphism

Wrap the part of code you wish to test (your "original" variant) with the `{% optimum 'experimentHandle' %}` tag like so:

```html
{% optimum 'bannerTypes' %}
   // Original Variant Code
   <img src="original_banner.jpg"/>  
{% endoptimum %}
```
Then create templates corresponding to each variant (except for "original") using the following naming convention:
`_optimum/{experimentHandle}/{variantHandle}.twig`

E.g:
 - `_optimum/bannerType/wideBanner.twig`
 - `_optimum/bannerType/narrowBanner.twig`

Inside each template paste the code for the variation you wish to test. E.g:
```html
   // Wide Banner Variant Code
   <img src="wide_banner.jpg" class="wide-banner"/>  
```
> **_NOTE:_**  Don't include the variant templates in your main template code. Optimum will automagically load the correct template at runtime. 

##### Method B: Explicit Variant Declaration

While method A is useful when you want to switch components, sometimes you may wish to switch the *location* of the component on the page (e.g Test different CTAs positions).
With method B, you can declare multiple `optimum` blocks with the second parameter being the variant:

E.g:
```html
{% optimum 'cta_position' 'top' %}
   <button>Buy now!</button>
{% endoptimum %}
// Some HTML
{% optimum 'cta_position' 'original' %}
   <button>Buy now!</button>  
{% endoptimum %}
// Some more HTML
{% optimum 'cta_position' 'bottom' %}
   <button>Buy now!</button>
{% endoptimum %}
```
The plugin will only compile the relevant variant.

##### Method C: Get only variant value 
In some cases there is no need to create multiple templates, as the value of the random variant can replace a constant in the code.
E.g, Suppose you have a blog and want to test different per-page values. Here is an example implementation for a hypothetical ([or is it?](https://www.craftcmsplugins.com/blog/index)) `recordsPerPage` experiment:
```html
{% set variant = optimumGetVariant('recordsPerPage') %}
{% set perPage = variant is same as ('original') ? 6 : 9 %} // Or use a switch statement if you have more than 2 variants
{% paginate query.limit(perPage) as pageInfo, pageEntries %}
// Pagination code
```

#### 4. Fire the event
Send experiment and variant to your tracking platform:
```html
<script>
{{ optimumFireEvent('bannerTypes') | raw }}
</script>
```
By default, this will send the event to GA4. e.g:
```js
gtag('event','bannerTypes', {'bannerTypes':'Wide Banner'});
```
You can modify the tracking code by overriding the `fireEvent` setting:
1. Create a new file in your config folder called `optimum.php`
2. Add the following code:
```php
return [
    'fireEvent' => function($experimentHandle, $variantName) {
        // Your custom tracking code here,e.g:
        mixpanel.track("$experimentHandle}", {
            "variant": "$variantName"
        });
    }
];
```

#### 5. Test your variants
Now that everything is set up, the plugin will randomize a variant and persist it in a cookie, to keep the experience consistent per-user.
You can test your variants (and the original) by adding a `?optimum={variant}` query parameter to your URL.
E.g `?optimum=wideBanner` or `?optimum=original`. The plugin will disregard the parameter if the value does not correspond to one of the variants.

#### 6. Set a Custom Dimension in GA4 (Only if using GA4 for tracking)
The last piece of the puzzle is telling GA4 to aggregate the events sent from your site into a custom dimension.
1. Open GA for your property and go to **Configure->Custom Definitions**
2. Click on the **Create custom dimensions** button
3. In the modal fill in the following details:
    - **Dimension Name** : Descriptive name. Can be anything you want. 
    - **Scope** : Event 
    - **Event parameter** :  Experiment handle (e.g `bannerType`). 
4. Click "Save"
------
![Screenshot 2023-07-14 094539](https://github.com/matfish2/craft-activity-log/assets/1510460/f56e17d4-7c4e-4f72-b6d6-541c4882c228)

Et voila:

![Screenshot 2023-07-14 094522](https://github.com/matfish2/craft-activity-log/assets/1510460/8e7902c4-ac97-456d-84f2-ed208de39eab)
All Done! Once GA has collected enough data, you can start comparing the performance of the different cohorts/test groups:

![custom-dimension](https://user-images.githubusercontent.com/1510460/202857414-1802f590-8550-4ba3-b71d-2167aa0b0140.png)

If using a different tracking platform, you will need to set up the tracking manually.

### Troubleshooting
Before opening an issue please make sure that:
1. Cookies are enabled 
2. Caching is disabled on the testable page (e.g Blitz), as plugin decides in real-time which variant to serve.
3. If using GA4 for tracking: GTM is installed on the page (type `gtag` in the console to verify).
4. If you edit an existing experiment (It is recommended not to do so once it has gone live), you need to delete the template cache, so it will recompile with the fresh details.

### Caveats

- Code inside the `optimum` tag is scoped. Variables defined inside the block containing the original variation (or in the variant templates) will not be available externally.

### Local Development
When developing locally, if using GA4 tacking code, you are likely not going to have `gtag` installed, which will result in the following console error:
```
Uncaught ReferenceError: gtag is not defined
```

While there is no issue with ignoring this for development, for the sake of completion, and to see what arguments are being sent to GA, you may want to add a dummy `gtag` function in your `<head>` section:
```twig  
{% if (getenv('CRAFT_ENVIRONMENT') is same as ('dev')) %}
  <script>
      function gtag() {
        console.log(arguments)
       }
   </script>
{% endif %}
```
You can use the same method to mock other tracking functions.

### License

You can try Optimum in a development environment for as long as you like. Once your site goes live, you are
required to purchase a license for the plugin. License is purchasable through the [Craft Plugin Store](https://plugins.craftcms.com/optimum).

For more information, see Craft's [Commercial Plugin Licensing](https://craftcms.com/docs/4.x/plugins.html#commercial-plugin-licensing).
