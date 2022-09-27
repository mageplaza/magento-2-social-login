# The Most Popular Magento 2 Social Login Extension

**Magento 2 Social Login extension** is designed for quick login to your Magento store without procesing complex register steps. Let say goodbye the complicated registration process and ignore a lot of unnecessarily required fields. *Magento 2 Social Login extension* is simply and powerful tool to integrate your Magento customer account to Facebook, Google Plus, Twitter, and LinkedIn channel. Logging in via the social medias is the great idea to enhance your customer’s satisfaction.

## Highlight features for Social Login

- Quickly login step with five most common social channels
- Easy to change the personal information after registering
- The biggest preparation step for the loyalty of customers

[![Latest Stable Version](https://poser.pugx.org/mageplaza/magento-2-social-login/v/stable)](https://packagist.org/packages/mageplaza/magento-2-social-login)
[![Total Downloads](https://poser.pugx.org/mageplaza/magento-2-social-login/downloads)](https://packagist.org/packages/mageplaza/magento-2-social-login)

## 1. Mageplaza Social Login Documentation

- [Installation guide](https://www.mageplaza.com/install-magento-2-extension/)
- [User Guide](https://docs.mageplaza.com/social-login-m2/index.html)
- [Download from our Live site](https://www.mageplaza.com/magento-2-social-login-extension/)
- [Get Free Support](https://github.com/mageplaza/magento-2-social-login/issues)
- Get premium support from Mageplaza: [Purchase Support package](https://www.mageplaza.com/magento-2-extension-support-package/)
- [Mageplaza Sale Page](https://www.mageplaza.com/magento-2-social-login-extension/)
- [Releases](https://github.com/mageplaza/magento-2-social-login/releases)
- [License](https://www.mageplaza.com/LICENSE.txt)


## 2. How to install Magento 2 Social Login


## ✓ Install Social Login via composer (recommend)
Run the following command in Magento 2 root folder:


With Marketing Automation (recommend):
```
composer require mageplaza/magento-2-social-login mageplaza/module-smtp
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
```

Without Marketing Automation:
```
composer require mageplaza/magento-2-social-login
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
```


### ✓ Install ready-to-paste package

- Download the latest version at [Mageplaza.com](https://www.mageplaza.com/magento-2-social-login-extension/)
-  [Installation guide](https://www.mageplaza.com/install-magento-2-extension/)



## 3. Social Login FAQs

#### Q: When I click on Login link, the popup does't work
A: You can read https://github.com/mageplaza/magento-2-social-login/issues/39

#### Q: I am using custom theme, it is compatible with our design?
A: We have developed Social Login based on Magento 2 coding standard and best practice test on Magento Community and Magento Enterpise site. So it is compatible with themes and custom designs. Ask Magento community on http://magento.stackexchange.com/ or https://github.com/mageplaza/magento-2-social-login/issues/

#### Q: Can I install it by myself?
A: Yes, you absolutely can! You can install it like installing any extensions to website, follow our Installation Guide http://docs.mageplaza.com/kb/installation.html. User guide: https://docs.mageplaza.com/social-login-m2/index.html

#### Q: I got this message `Erro: invalid_scope`
A: Read this https://github.com/mageplaza/magento-2-social-login/issues/42

#### Q: I got error: `Mageplaza_Core has been already defined`
A: Read solution: https://github.com/mageplaza/module-core/issues/3

#### Q: My site is down
A: Please follow this guide: https://www.mageplaza.com/blog/magento-site-down.html



## 4. Social Login user guide


Customers are not patient enough to fill a lot of required information while those are available in social account as Facebook, LinkedIn,.... [Mageplaza Social Login extension](https://www.mageplaza.com/magento-2-social-login-extension/), your customers only need to click on the social button and all necessary information is completed automatically.That is the main reason why **Magento 2 Social Login extension** is considered as the great solution for that convenience.

Login to Magento Admin and do as the following:

![Magento 2 social login](https://cdn.mageplaza.com/docs/social-settings.gif)

### General Configuration


#### Enable Social Login


Go to `Admin Panel > Social Login > Settings > General`

![enable social login](https://i.imgur.com/jNcIDpg.png)

Select `Yes` option in order to allow customers to sign in quickly via social channels they are using.

#### Setting Social Login popup effect


Go to `Admin Panel > Social Login > Settings > General`

Right after activating, all of available social buttons are shown on Sign In box while the page will appear instantly on Home page without any navigation to other site.

Admin can choose one of nice effects as you need by block in Popup Effect field.

![Magento 2 social login popup](https://i.imgur.com/Bnv7qTn.png)

#### Custom color of checkbox


Go to `Admin Panel > Social Login > Settings > General`

**Mageplaza Social Login** provides a Magento 2 default color and **8** popular colors for your design, you can choose custom color which fit with your store design.

![social color](https://i.imgur.com/kZTaFjX.png)

Especially, now we also support you 9th color that you can freely custom depends on needs of yourself. It is unlimited color to design the style of **Sign In** box

![custom color social login](https://i.imgur.com/o1Ggu8F.png)

#### Facebook Sign In


##### How to configure Facebook Login


Go to `Admin Panel > Social Login > Settings > Facebook`

![Magento 2 social login with facebook sign-in button](https://i.imgur.com/wBtVqY9.png)

* Choose `Yes` or `No` to `enable or disable` **Facebook Sign In button** on the front-end with Facebook App ID and Facebook App Secret.

* If customers login via Facebook App, you can send email notification about their account’s password on your site or not, that depends on setting in Send Password to Customer field.

##### Login using Facebook account


![Magento 2 Login using Facebook](https://i.imgur.com/5zYCdnw.png)

The login box will display as popup checkbox after clicking on **Facebook Sign In** button.

#### Google Sign In


##### How to configure Google Login


Go to `Admin Panel > Social Login > Settings > Google`

![Magento 2 social login with google sign-in button](https://i.imgur.com/jB6A0t1.png)

* Choose `Yes` or `No` to `enable or disable` **Google Sign In button** on the front-end with Client ID and Client Secret.

* If customers login via Google, you can send email notification about their account’s password on your site or not, that depends on setting in **Send Password to Customer** field.

#### Login using Google account


![Magento 2 login using Google](https://i.imgur.com/htWnJ7p.png)

The login box will display as popup checkbox after clicking on **Google Sign In** button.

#### Twitter Sign In


##### How to configure Twitter Login


Go to `Admin Panel > Social Login > Settings > Twitter`

![Magento 2 social login with  twitter sign-in button](https://i.imgur.com/9SRcWbU.png)

* Choose `Yes` or `No` to enable or disable **Twitter Sign In button** on the front-end with Consumer Key and Consumer Secret.

* If customers login via Twitter, you can send email notification about their account’s password on your site or not, that depends on setting in Send Password to Customer field.

##### Login using Twitter account


![Magento 2 Login using Twitter](https://i.imgur.com/fYF1sRc.png)

The login box will display as popup checkbox after clicking on **Twitter Sign In** button.

#### LinkedIn Sign In


##### How to configure LinkedIn Login


Go to `Admin Panel > Social Login > Settings > LinkedIn`

![Magento 2 social login with  linkedin sign in button](https://i.imgur.com/SqCKAB7.png)

* Choose `Yes` or `N`o to enable or disable **LikedIn Sign In button** on the front-end with API Key and Client Key.

* If customers login via LinkedIn, you can send email notification about their account’s password on your site or not, that depends on setting in **Send Password to Customer** field.

##### Login using LinkedIn account


![Magento 2 Login using LinkedIn](https://i.imgur.com/IKERf5H.png)

The login box will display as popup checkbox after clicking on **LinkedIn Sign In** button.


**Mageplaza extensions on Magento Marketplace, Github**


☞ [Magento 2 One Step Checkout extension](https://marketplace.magento.com/mageplaza-magento-2-one-step-checkout-extension.html)

☞ [Magento 2 SEO Module](https://marketplace.magento.com/mageplaza-magento-2-seo-extension.html)

☞ [Magento 2 Blog extension](https://marketplace.magento.com/mageplaza-magento-2-blog-extension.html)

☞ [Magento 2 Layered Navigation extension](https://github.com/mageplaza/magento-2-ajax-layered-navigation)

☞ [Magento 2 Blog module](https://github.com/mageplaza/magento-2-blog)

☞ [Magento 2 Social Login module](https://github.com/mageplaza/magento-2-social-login)

☞ [Magento 2 SEO Module](https://github.com/mageplaza/magento-2-seo)

☞ [Magento 2 SMTP Module](https://github.com/mageplaza/magento-2-smtp)

☞ [Magento 2 Product Slider Module](https://github.com/mageplaza/magento-2-product-slider)

☞ [Magento 2 Banner Module](https://github.com/mageplaza/magento-2-banner-slider)


**People alse search:**
- social login magento 2
- magento 2 facebook login
- mageplaza social login magento 2
- magento 2 social login extension
- magento 2 login with facebook
- magento 2 social login extension free
- magento 2 login popup extension free
- magento 2 social login free
- magento 2 social login free extension
- mobile number login magento 2 extension free

