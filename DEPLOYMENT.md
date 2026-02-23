# دليل رفع الـ Package

## الطريقة 1: رفع على Git Repository (Private/Public)

### الخطوات:

1. **إنشاء Git Repository جديد:**
   ```bash
   cd packages/ML/PaymentGateway
   git init
   git add .
   git commit -m "Initial commit: ML Payment Gateway Package"
   ```

2. **ربط الـ Repository:**
   ```bash
   git remote add origin https://github.com/your-org/ml-payment-gateway.git
   git branch -M main
   git push -u origin main
   ```

3. **إضافة الـ Repository في المشاريع الأخرى:**

   في `composer.json` للمشروع:
   ```json
   {
       "repositories": [
           {
               "type": "vcs",
               "url": "https://github.com/your-org/ml-payment-gateway.git"
           }
       ],
       "require": {
           "mlquarizm/payment-gateway": "dev-main"
       }
   }
   ```

   ثم:
   ```bash
   composer require mlquarizm/payment-gateway:dev-main
   ```

## الطريقة 2: رفع على Packagist (Public Package)

### الخطوات:

1. **رفع على GitHub/GitLab:**
   - أنشئ repository على GitHub
   - ارفع الكود

2. **تسجيل على Packagist:**
   - اذهب إلى https://packagist.org
   - سجل حساب جديد
   - اضغط "Submit"
   - أدخل رابط الـ repository
   - Packagist سيقوم بفحص الـ package تلقائياً

3. **الاستخدام:**
   ```bash
   composer require mlquarizm/payment-gateway
   ```

## الطريقة 3: استخدام Local Path (للتنمية فقط)

في `composer.json` للمشروع الرئيسي:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/ML/PaymentGateway"
        }
    ],
    "require": {
        "mlquarizm/payment-gateway": "*"
    }
}
```

ثم:
```bash
composer require mlquarizm/payment-gateway
```

## ملاحظات مهمة:

1. **Versioning:** استخدم Git tags للإصدارات:
   ```bash
   git tag -a v1.0.0 -m "Version 1.0.0"
   git push origin v1.0.0
   ```

2. **Composer.json:** تأكد من تحديث:
   - `homepage`
   - `support.issues`
   - `support.source`

3. **README:** تأكد من وجود توثيق كامل

4. **License:** تأكد من وجود ملف LICENSE
