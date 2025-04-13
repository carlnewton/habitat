window.toggleHeart = function(postId) {
    fetch("/api/heart/" + postId, {
        method: 'post',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    }).then((response) => {
        return response.json()
    }).then((res) => {
        let heartsButton = document.querySelector('#hearts-' + postId);
        let heartsIcon = document.querySelector('#hearts-' + postId + ' .bi');
        let heartsCountText = document.querySelector('#hearts-' + postId + ' .heart-count');
        heartsCountText.innerText = parseInt(res.count);

        switch (res.result) {
            case 'added':
                navigator.vibrate && navigator.vibrate(20);
                heartsButton.classList.add('text-danger');
                heartsButton.classList.remove('text-muted');
                heartsIcon.classList.add('bi-heart-fill');
                heartsIcon.classList.remove('bi-heart');
                break;
            case 'removed':
                heartsButton.classList.add('text-muted');
                heartsButton.classList.remove('text-danger');
                heartsIcon.classList.add('bi-heart');
                heartsIcon.classList.remove('bi-heart-fill');
                break;
        }
    }).catch((error) => {
        console.log(error)
    })
}
