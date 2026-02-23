# 🚀 رفع الـ Package بسرعة

## الطريقة السريعة (Git Repository)

```bash
# 1. اذهب لمجلد الـ package
cd packages/ML/PaymentGateway

# 2. ابدأ Git repository
git init
git add .
git commit -m "Initial commit: ML Payment Gateway Package v1.0.0"

# 3. اربط الـ repository (استبدل الرابط برابطك)
git remote add origin https://github.com/your-org/ml-payment-gateway.git
git branch -M main
git push -u origin main

# 4. أنشئ tag للإصدار
git tag -a v1.0.0 -m "Version 1.0.0"
git push origin v1.0.0
```

## استخدام الـ Package في مشروع آخر

### في `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/your-org/ml-payment-gateway.git"
        }
    ],
    "require": {
        "ml/payment-gateway": "^1.0"
    }
}
```

### ثم:

```bash
composer require ml/payment-gateway
```

## ملاحظات:

- ✅ استبدل `your-org/ml-payment-gateway` برابط الـ repository الفعلي
- ✅ استبدل `info@ml.com` بالإيميل الصحيح في `composer.json`
- ✅ استبدل `homepage` و `support` في `composer.json` بروابطك
