# E-PİN Dijital Lisans Platformu – Tema & Otomatik Teslimat Paketi

Koyu temalı, neon vurgularla güçlendirilmiş OH Digital WordPress teması ile otomatik E-PİN teslimatı yapan yardımcı eklentiyi bu depoda bulabilirsiniz. WooCommerce 8.x ve PHP 8.1+ uyumlu yapı; kod havuzu, Terra Wallet bakiyesi, çoklu ödeme ağ geçitleri ve gelişmiş yönetim panelleriyle dijital lisans mağazasını uçtan uca hazırlar.

## Dizin Yapısı

- `theme/` – OH Digital WordPress teması
  - `assets/css|js|scss` – Dark UI için minify edilmiş stil ve etkileşim scriptleri
  - `inc/` – Tema kurulumu, SEO meta, lazyload ve WooCommerce override sınıfları
  - `templates/` – Hero slider, kategori filtreleri, checkout formu ve My Account görünümleri
- `plugins/oh-digital-delivery/` – Kod havuzu, REST API, cüzdan ve ticket servislerini içeren eklenti
- `demo/` – Örnek lisans CSV dosyası ve içerik içe aktarma XML’i

## Kurulum Adımları

1. **WordPress & WooCommerce**: WordPress 6.x ve WooCommerce 8.x kurulu olmalıdır.
2. **Tema**: `theme` klasörünü `oh-digital-theme.zip` haline getirip yükleyin veya doğrudan `/wp-content/themes` dizinine kopyalayın.
3. **Eklenti**: `plugins/oh-digital-delivery` klasörünü sıkıştırarak yükleyin ve etkinleştirin. Aktivasyon kod havuzu tablosunu oluşturur.
4. **Zorunlu Eklentiler**: Terra Wallet, PayTR, İyzico, Shopier, Paywant ve CoinPayments/NOWPayments gateway eklentilerini kurup test modunda yapılandırın.
5. **Demo Verisi**: `demo/demo-content.xml` dosyasını WordPress içe aktarma aracıyla yükleyin. Kod havuzu için `demo/license-pool-sample.csv` dosyasını eklenti admin panelinden içe aktarın.
6. **SEO & Performans**: Yoast SEO veya RankMath ayarlarını yapılandırın. Tema otomatik olarak JSON-LD Product şeması, OpenGraph meta ve lazyload optimizasyonu ekler.
7. **Cron Görevleri**: `oh_digital_check_stock_levels` cron’u stok takibi için etkinleştirilir; sunucunuzun WP cron’u tetikleyebildiğinden emin olun.

## Öne Çıkan Özellikler

- **Tema UI/UX**: Hero slider, neon vurgulu ürün kartları, AJAX kategori filtreleri, mini-cart popup ve responsive 1440/1200/992/768/576 kırılımları.
- **Checkout Deneyimi**: Terra Wallet bakiyesi ile ödeme, otomatik bakiye kontrolü, gateway tabları ve düşük bakiye uyarıları.
- **My Account**: Lisans maskesi, PDF çıktı, Terra Wallet hareketleri, destek ticketları ve güvenlik bilgileri.
- **Otomatik Teslimat**: AES-256 şifreli kod havuzu, stok uyarıları, e-posta bildirimleri, REST API ile lisans görüntüleme.
- **SEO & Performans**: Product Schema, breadcrumbs, OG/Twitter meta, lazyload, deferred script ve inline critical CSS.

## Kabul Testleri

- Tema ve eklenti kurulumu sonrası demo içerik sorunsuz içe aktarılır.
- PUBG UC örnek ürünü için satın alma → ödeme → kod teslim akışı otomatik çalışır.
- Tüm gateway’ler test modunda sipariş tamamlayabilir; Terra Wallet bakiyesi yetersizse checkout’ta uyarı görünür.
- Admin paneli; stok grafikleri, sipariş listesi ve rapor sekmeleriyle doğru veri gösterir.
- “Lisanslarım” sekmesi maskeli kodları, PDF indir butonunu ve AJAX göster/gizle işlemlerini sunar.
- Lighthouse hedefleri: Performance ≥ 85, SEO ≥ 90, Best Practices ≥ 90 (örnek demo sayfalarında ölçümlenmiştir).

## Faydalı Komutlar

- Kod kontrolleri: `php -l plugins/oh-digital-delivery/**/*.php`
- Sass düzenlemeleri sonrası manuel build gerekli ise `theme/assets/scss/theme.scss` dosyasındaki değişiklikleri `assets/css/theme.css` içine yansıtın.

Detaylı bakım adımları ve REST uç noktaları için `plugins/oh-digital-delivery` dizinindeki sınıf dosyalarına göz atabilirsiniz.
