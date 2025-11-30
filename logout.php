<a href="#" id="logoutBtn" class="btn btn-danger">Logout</a>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.getElementById("logoutBtn").addEventListener("click", function(e) {
    e.preventDefault();

    Swal.fire({
        title: "Are you sure you want to log out?",
        text: "You will be signed out from your account.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, log me out"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "logout.php";
        }
    });
});
</script>
