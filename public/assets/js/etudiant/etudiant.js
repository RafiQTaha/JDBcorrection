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
    $('#datatables_etudiant').DataTable({
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });
    
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
    

    $("#search").on("click", async (e) => {
        e.preventDefault();
        let idSemestre = $("#semestre").val();
        console.log(idSemestre);
        if(idSemestre == ""){
            Toast.fire({
                icon: 'error',
                title: 'Veuillez selectionner un semestre!',
                })
            return;
        }
        const icon = $("#search i");
        icon.removeClass('fa-search').addClass("fa-spinner fa-spin");
        
        try {
          const request = await axios.get('/etudiant/list/'+idSemestre);
          const response = request.data;
         
          icon.addClass('fa-search').removeClass("fa-spinner fa-spin ");
          console.log(response);
        } catch (error) {
          const message = error.response.data;
          console.log(error, error.response);
          icon.addClass('fa-search').removeClass("fa-spinner fa-spin ");
          
        }
    })
})

