const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    },
    })

    
    $(document).ready(function  () {
        var table = $("#datatables_gestion_session").DataTable({
            lengthMenu: [
                [10, 15, 25, 50, 100, 20000000000000],
                [10, 15, 25, 50, 100, "All"],
            ],
            order: [[0, "desc"]],
            ajax: "/etudiant/session/list",
            processing: true,
            serverSide: true,
            deferRender: true,
            language: {
                url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
            },
        });
    $("#etablissement, #enseignant").select2()
    $("#etablissement").on('change', async function (){
        const id_etab = $(this).val();
        let response = ""
        if(id_etab != "") {
            const request = await axios.get('/api/formation/'+id_etab);
            response = request.data
        }
        $('#formation').html(response).select2();
    })
    $("#formation").on('change', async function (){
        const id_for = $(this).val();
        let response = ""
        if(id_for != "") {
            const request = await axios.get('/api/promotion/'+id_for);
            response = request.data
        }
        $('#promotion').html(response).select2();
    })
    $("#promotion").on('change', async function (){
        const id = $(this).val();
        let response = ""
        if(id != "") {
            const request = await axios.get('/api/semestre/'+id);
            response = request.data
        }
        $('#semestre').html(response).select2();
    })
    
    $("#generer").on("click", function(){
        let idSemestre = $("#semestre").val();

        if(!idSemestre || idSemestre == ""){
            Toast.fire({
                icon: 'error',
                title: 'Veuillez selectionner un semestre!',
                })
            return;
        }
        $("#generer-modal").modal("show")
    })
    $("#save").on("submit", async function(e){
        e.preventDefault();
        let idSemestre = $("#semestre").val();
        console.log(idSemestre);
       
        let modalAlert = $("#generer-modal .modal-body .alert")
        modalAlert.remove();
        const icon = $("#save .btn i");
        // const button = $("#import-group-ins .btn");
        icon.removeClass('fa-check-circle').addClass("fa-spinner fa-spin");
        var formData = new FormData($("#save")[0]);
        formData.append("semestre", idSemestre)
        console.log(formData);
        table.ajax.reload()
        try {
        const request = await axios.post("/etudiant/session/generer", formData, {
            headers: {
            "Content-Type": "multipart/form-data",
            },
        });
        const data = await request.data;
        $("#generer-modal .modal-body").prepend(
            `<div class="alert alert-success">
                <p>${data}</p>
            </div>`
        );
        icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
        
        } catch (error) {
        const message = error.response.data;
        console.log(error, error.response);
        modalAlert.remove();
        $("#generer-modal .modal-body").prepend(
            `<div class="alert alert-danger">${message}</div>`
        );
        icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
        }
        setTimeout(() => {
        $(".modal-body .alert").remove();
        }, 2500) 
    })
})

