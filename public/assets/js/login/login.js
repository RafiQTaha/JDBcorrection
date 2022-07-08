const signUpButton = document.getElementById('signUp');
const signInButton = document.getElementById('signIn');
const container = document.getElementById('container');

signUpButton.addEventListener('click', () => {
	container.classList.add("right-panel-active");
});

signInButton.addEventListener('click', () => {
	container.classList.remove("right-panel-active");
});

$(document).ready(function(){



	$("#singup").on("submit", async function(e) {
		e.preventDefault();
		var button = $(this).find("button")
		button.html("<i class='fas fa-spinner fa-spin'></i>");
        var formData = new FormData($(this)[0]);
       
        try {
        const request = await axios.post("/session", formData);
        const data = await request.data;
        
        // button.html("Login")
        
	} catch (error) {
		const message = error.response.data;
        console.log(error, error.response);
       
		// button.html("Login")
        }
	})

})