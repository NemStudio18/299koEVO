document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('.btn-add-respond').forEach(function (item) {
        item.addEventListener("click", function (e) {
            e.preventDefault();
            var $form = document.querySelector('#comments-add-respond');
            var parent_id = item.getAttribute('data-id');
            var $comment = document.querySelector('#comment' + parent_id);
            document.querySelector("#comments-title").textContent = document.querySelector('#comment' + parent_id + "Infos").getAttribute('data-author');
            document.querySelector('#commentParentId').value = parent_id;
            $comment.after($form);
            var $aRem = document.querySelector("#comments-cancel-respond");
            $aRem.style.display = "block";
        });
    });

    if (document.querySelector('#comments-cancel-respond')) {
        document.querySelector('#comments-cancel-respond').addEventListener("click", function (e) {
            e.preventDefault();
            var $aRem = document.querySelector("#comments-cancel-respond");
            $aRem.style.display = "none";
            var $form = document.querySelector('#comments-add-respond');
            var $container = document.querySelector('#comments-add-container');
            document.querySelector("#comments-title").textContent = document.querySelector("#comments-title").getAttribute('data-title');
            document.querySelector('#commentParentId').value = 0;
            $container.after($form);
        });
    }

});