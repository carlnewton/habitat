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
        let heartsCountText = document.querySelector('#hearts-' + postId + ' .heart-count');
        heartsCountText.innerText = parseInt(res.count);

        switch (res.result) {
            case 'added':
                heartsButton.classList.add('text-danger');
                heartsButton.classList.remove('text-muted')
                break;
            case 'removed':
                heartsButton.classList.add('text-muted')
                heartsButton.classList.remove('text-danger');
                break;
        }
    }).catch((error) => {
        console.log(error)
    })
}
