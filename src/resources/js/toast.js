document.addEventListener("DOMContentLoaded", function () {
    if (window.craftToastMessage) {
        let toast = document.createElement("div");
        toast.className = "global-toast " + window.craftToastMessage.type;
        toast.innerText = window.craftToastMessage.text;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add("show");
        }, 100);

        setTimeout(() => {
            toast.classList.remove("show");
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }
});