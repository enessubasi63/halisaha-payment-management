// validation.js
// Formlardaki alanların validasyonunu yapan fonksiyonlar.
// Örneğin, rezervasyon formundaki telefon numarasının doğru formatta girilip girilmediğini kontrol edebiliriz.

document.addEventListener('DOMContentLoaded', function () {
    const reservationForm = document.querySelector('#reservationForm');
    if (reservationForm) {
        reservationForm.addEventListener('submit', function (e) {
            let isValid = true;

            // Örnek: Telefon alanı validasyonu
            const phoneInput = reservationForm.querySelector('input[name="telefon"]');
            const phoneRegex = /^[0-9\-\+\s]{10,15}$/;

            if (phoneInput && !phoneRegex.test(phoneInput.value.trim())) {
                isValid = false;
                phoneInput.classList.add('is-invalid');
            } else {
                phoneInput && phoneInput.classList.remove('is-invalid');
            }

            // Ad Soyad validasyonu
            const nameInput = reservationForm.querySelector('input[name="ad_soyad"]');
            if (nameInput && nameInput.value.trim().length < 3) {
                isValid = false;
                nameInput.classList.add('is-invalid');
            } else {
                nameInput && nameInput.classList.remove('is-invalid');
            }

            // Eğer geçerli değilse form gönderilmesini engelle
            if (!isValid) {
                e.preventDefault();
                alert("Lütfen formu geçerli bilgilerle doldurunuz.");
            }
        });
    }
});
