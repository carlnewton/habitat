const storageOptions = document.querySelectorAll('input[name="storageOption"]');

storageOptions.forEach(radio => {
    radio.value === 's3' && radio.checked && toggleS3Options(true);
    radio.addEventListener('change', (event) => {
        toggleS3Options(event.target.value === 's3');
    });
});

function toggleS3Options(show) {
    if (show) {
        document.getElementById('s3Options').classList.remove('d-none');
    } else {
        document.getElementById('s3Options').classList.add('d-none');
    }
}
