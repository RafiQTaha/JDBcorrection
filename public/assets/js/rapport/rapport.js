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
    
    $("#etablissement").select2()
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
    
    $('body').on("click","#export", function(e){
        e.preventDefault();
        let idSemestre = $("#semestre").val();
        if(!idSemestre || idSemestre == ""){
            Toast.fire({
                icon: 'error',
                title: 'Veuillez selectionner un semestre!',
                })
            return;
        }
        window.open('/etudiant/rapport/export_etat_Notes/'+idSemestre, '_blank');
    })
    

    $("#search").on("click", async (e) => {
        e.preventDefault();
        let idSemestre = $("#semestre").val();
        console.log(idSemestre);
        if(!idSemestre || idSemestre == ""){
            Toast.fire({
                icon: 'error',
                title: 'Veuillez selectionner un semestre!',
                })
            return;
        }
        const icon = $("#search i");
        icon.removeClass('fa-search').addClass("fa-spinner fa-spin");
        try {
            const request = await axios.get('/etudiant/rapport/list/'+idSemestre);
            const response = request.data;
            
            icon.addClass('fa-search').removeClass("fa-spinner fa-spin ");
            // console.log(response);
            if ($.fn.DataTable.isDataTable(".content #datatables_etudiant")) {
                $('.content #datatables_etudiant').DataTable().clear().destroy();
            }
            $(".content").html(response)
            $('.content #datatables_etudiant').DataTable({
                lengthMenu: [
                    [10, 15, 25, 50, 100, 20000000000000],
                    [10, 15, 25, 50, 100, "All"],
                ],
                language: {
                    url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
                },
            });
        } catch (error) {
          const message = error.response.data;
          console.log(error, error.response);
          icon.addClass('fa-search').removeClass("fa-spinner fa-spin ");
          
        }
    })
   

    $("#import").on("click", function(){
        $("#importer-modal").modal("show")
    })
    $("#save_import").on("submit",async function(e){
        e.preventDefault();
        let modalAlert = $("#importer-modal .modal-body .alert")
        modalAlert.remove();
        if($('.myfile').val() == ""){
            $("#importer-modal .modal-body").prepend(
                `<div class="alert alert-danger">Merci de choisir Un fichier!</div>`
            );
            setTimeout(() => {
                $(".modal-body .alert").remove();
            }, 2500) 
            return;
        }
        const icon = $("#save_import .btn i");
        // const button = $("#import-group-ins .btn");
        icon.removeClass('fa-check-circle').addClass("fa-spinner fa-spin");
        var formData = new FormData($("#save_import")[0]);
        console.log(formData);
        try {
        const request = await axios.post("/etudiant/rapport/import", formData, {
            headers: {
            "Content-Type": "multipart/form-data",
            },
        });
        const data = await request.data;
        $("#importer-modal .modal-body").prepend(
            `<div class="alert alert-success">
                <p>${data}</p>
            </div>`
        );
        icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
        
        } catch (error) {
        const message = error.response.data;
        console.log(error, error.response);
        modalAlert.remove();
        $("#importer-modal .modal-body").prepend(
            `<div class="alert alert-danger">${message}</div>`
        );
        icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
        }
        setTimeout(() => {
        $(".modal-body .alert").remove();
        }, 2500) 
    })
    
})

