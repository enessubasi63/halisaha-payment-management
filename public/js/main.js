// main.js
// Uygulama genelinde kullanılacak özel JS fonksiyonları.
// Burada menü açma/kapama, saha seçimine göre seans filtreleme, form validasyonu ve mobil menü iyileştirmeleri bulunur.

document.addEventListener('DOMContentLoaded', function () {

    // ====== 1. Saha Seçimine Göre Seans Filtreleme (AJAX ile Dinamik Yükleme) ======
    const sahaSelect = document.querySelector('select[name="saha_id"]');
    const seansSelect = document.querySelector('select[name="seans_id"]');
    const tarihInput = document.querySelector('input[name="tarih"]');

    if (sahaSelect && seansSelect && tarihInput) {
        function fetchSeanslar() {
            const selectedSaha = sahaSelect.value;
            const selectedTarih = tarihInput.value;

            // Seha veya tarih seçilmemişse seansları temizle
            if (!selectedSaha || !selectedTarih) {
                seansSelect.innerHTML = '<option value="">Seçiniz</option>';
                return;
            }

            // AJAX ile seansları çek
            fetch(`/views/dashboard/get_seanslar.php?saha_id=${selectedSaha}&tarih=${selectedTarih}`)
            .then(response => response.json())
            .then(data => {
                console.log('Seanslar Cevap:', data); // Bu satırı ekleyin
                seansSelect.innerHTML = '<option value="">Seçiniz</option>';
                data.forEach(seans => {
                    const option = document.createElement('option');
                    option.value = seans.id;
                    option.text = seans.text;
                    if (seans.disabled) {
                        option.disabled = true;
                    }
                    seansSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Seanslar çekilirken hata oluştu:', error);
            });
        
        }

        // Saha veya tarih değiştiğinde seansları güncelle
        sahaSelect.addEventListener('change', fetchSeanslar);
        tarihInput.addEventListener('change', fetchSeanslar);

        // Sayfa yüklendiğinde seansları güncelle (eğer saha ve tarih seçiliyse)
        fetchSeanslar();
    }

    // ====== 2. Form Doğrulama ======
    const reservationForm = document.getElementById('reservationForm');
    if (reservationForm) {
        reservationForm.addEventListener('submit', function (e) {
            let isValid = true;

            const adSoyad = reservationForm.querySelector('input[name="ad_soyad"]');
            const telefon = reservationForm.querySelector('input[name="telefon"]');
            const tarih = reservationForm.querySelector('input[name="tarih"]');
            const saha = reservationForm.querySelector('select[name="saha_id"]');
            const seans = reservationForm.querySelector('select[name="seans_id"]');

            // Ad Soyad Validasyonu
            if (adSoyad.value.trim().length < 3) {
                isValid = false;
                adSoyad.classList.add('is-invalid');
                // Hata mesajını göster
                if (!adSoyad.nextElementSibling || !adSoyad.nextElementSibling.classList.contains('invalid-feedback')) {
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.innerText = 'Ad Soyad en az 3 karakter olmalıdır.';
                    adSoyad.parentNode.appendChild(feedback);
                }
            } else {
                adSoyad.classList.remove('is-invalid');
                if (adSoyad.nextElementSibling && adSoyad.nextElementSibling.classList.contains('invalid-feedback')) {
                    adSoyad.nextElementSibling.remove();
                }
            }

            // Telefon Validasyonu (Basit regex)
            const phoneRegex = /^[0-9\-\+\s]{10,15}$/;
            if (!phoneRegex.test(telefon.value.trim())) {
                isValid = false;
                telefon.classList.add('is-invalid');
                if (!telefon.nextElementSibling || !telefon.nextElementSibling.classList.contains('invalid-feedback')) {
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.innerText = 'Geçerli bir telefon numarası giriniz.';
                    telefon.parentNode.appendChild(feedback);
                }
            } else {
                telefon.classList.remove('is-invalid');
                if (telefon.nextElementSibling && telefon.nextElementSibling.classList.contains('invalid-feedback')) {
                    telefon.nextElementSibling.remove();
                }
            }

            // Tarih Validasyonu
            const today = new Date().toISOString().split('T')[0];
            if (tarih.value < today) {
                isValid = false;
                tarih.classList.add('is-invalid');
                if (!tarih.nextElementSibling || !tarih.nextElementSibling.classList.contains('invalid-feedback')) {
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.innerText = 'Geçerli bir tarih seçiniz (bugün veya ilerisi).';
                    tarih.parentNode.appendChild(feedback);
                }
            } else {
                tarih.classList.remove('is-invalid');
                if (tarih.nextElementSibling && tarih.nextElementSibling.classList.contains('invalid-feedback')) {
                    tarih.nextElementSibling.remove();
                }
            }

            // Saha ve Seans Validasyonu
            if (!saha.value) {
                isValid = false;
                saha.classList.add('is-invalid');
                if (!saha.nextElementSibling || !saha.nextElementSibling.classList.contains('invalid-feedback')) {
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.innerText = 'Lütfen bir saha seçiniz.';
                    saha.parentNode.appendChild(feedback);
                }
            } else {
                saha.classList.remove('is-invalid');
                if (saha.nextElementSibling && saha.nextElementSibling.classList.contains('invalid-feedback')) {
                    saha.nextElementSibling.remove();
                }
            }

            if (!seans.value) {
                isValid = false;
                seans.classList.add('is-invalid');
                if (!seans.nextElementSibling || !seans.nextElementSibling.classList.contains('invalid-feedback')) {
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.innerText = 'Lütfen bir seans seçiniz.';
                    seans.parentNode.appendChild(feedback);
                }
            } else {
                seans.classList.remove('is-invalid');
                if (seans.nextElementSibling && seans.nextElementSibling.classList.contains('invalid-feedback')) {
                    seans.nextElementSibling.remove();
                }
            }

            if (!isValid) {
                e.preventDefault(); // Formun gönderilmesini engelle
            }
        });
    }

    // ====== 3. Mobil Menü İyileştirmeleri ======
    // Bootstrap Navbar zaten mobil menüyü yönetiyor.
    // Ancak, kullanıcı deneyimini artırmak için menü kapandığında bazı işlemler yapabiliriz.

    const navbarCollapse = document.getElementById('navbarResponsive');
    if (navbarCollapse) {
        navbarCollapse.addEventListener('hide.bs.collapse', function () {
            // Menü kapanırken yapılacak işlemler
        });

        navbarCollapse.addEventListener('show.bs.collapse', function () {
            // Menü açıldığında yapılacak işlemler
        });
    }

    // ====== 4. Ekstra Genel Fonksiyonlar ======
    // Başka genel JS fonksiyonlarınızı burada ekleyebilirsiniz.

});
