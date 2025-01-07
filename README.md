# Coupon finisher for NeosRulez.Shop

A Neos CMS plugin which adds a coupon finisher to NeosRulez.Shop

## Installation

Just run:

```
composer require neosrulez/shop-couponfinisher
```

### Settings

```yaml
NeosRulez:
  Shop:
    CouponFinisher:
      Mail:
        senderMail: 'noreply@domain.tld'
        template:
          package: 'NeosRulez.Shop.CouponFinisher'
          fusionPath: 'NeosRulez/Shop/CouponFinisher/Mail/Coupon'
```

## Author

* E-Mail: mail@patriceckhart.com
* URL: http://www.patriceckhart.com 