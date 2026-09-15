# Официальный плагин TropaTT CRM для WooCommerce / WordPress

Модуль интеграции для интернет-магазинов на WordPress + WooCommerce. Обеспечивает прямую двустороннюю синхронизацию заказов, контактных данных и статусов с TropaTT CRM (`crm.ecommerce-gateway`).

## Возможности
- Двусторонняя реактивная синхронизация статусов заказов через REST API с защитой от эхо-петель (`Tropatt_Client::$suppress_echo`).
- Передача деталей заказов: товары, артикулы (SKU), количество, цены в минорных единицах, налоги, способы и стоимость доставки.
- Полная криптографическая защита HMAC-SHA256 в соответствии со спецификацией протокола Ingestion API.
- Встроенная кнопка быстрой проверки соединения (Ping-тест) в админке WooCommerce.

## Установка
1. Загрузите архив `tropatt-woocommerce.zip` через меню **Плагины** -> **Добавить новый** -> **Загрузить плагин**.
2. Активируйте плагин **TropaTT CRM E-Commerce Gateway**.
3. Перейдите в **WooCommerce** -> **Настройки** -> вкладка **TropaTT CRM**.
4. Введите URL шлюза, публичный ключ витрины (`stk_...`) и секретный ключ, полученные в TropaTT CRM.
5. Скопируйте сгенерированный URL входящих вебхуков в карточку витрины в CRM и сохраните настройки.

## Сборка архива

```bash
bash build.sh
```

Скрипт собирает `dist/tropatt-woocommerce.zip` (плагин упаковывается в каталог `tropatt-ecommerce/`, как требует установщик WordPress) и проверяет целостность архива.

## Лицензия

AGPL-3.0, та же лицензия, что и у проекта TropaTT (см. `LICENSE`).

---

# TropaTT CRM connector for WooCommerce / WordPress (EN)

A plugin for two-way order and status synchronisation between WooCommerce and the [TropaTT](https://github.com/Anton-Barinov/TropaTT) self-hosted CRM (module `crm.ecommerce-gateway`), with HMAC-SHA256 signed requests and echo-loop protection.

## Install

1. Run `bash build.sh` (or download `tropatt-woocommerce.zip` from the releases page).
2. In WordPress go to **Plugins → Add New → Upload Plugin**, choose the archive and activate *TropaTT CRM E-Commerce Gateway*.
3. Open **WooCommerce → Settings → TropaTT CRM**, fill in the gateway URL, the store public key (`stk_...`) and the secret, then run the connection test and save.

## Build

```bash
bash build.sh
```

## License

AGPL-3.0, the same licence as the TropaTT project (see `LICENSE`).
