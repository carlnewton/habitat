window.toggleHiddenCategory = function(categoryId) {
    fetch("/api/category/" + categoryId, {
        method: 'post',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    }).then((response) => {
        return response.json()
    }).then((res) => {
        document.querySelector('#showCategoryPosts').checked = res.showPosts;
    }).catch((error) => {
        console.log(error)
    })
}
