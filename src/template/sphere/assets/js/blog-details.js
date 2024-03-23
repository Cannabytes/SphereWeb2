function replay() {
    "use strict";

    let replayButtons = document.querySelectorAll('.reply a');

    replayButtons.forEach((element) => {
        element.addEventListener('click', () => {
            let parentDiv = element.parentElement;

            // Check if a comment box already exists in the parent element
            if (!parentDiv.querySelector('.comment')) {
                // Create a div element
                let commentDiv = document.createElement('div');
                commentDiv.classList = "comment mt-3 d-grid";

                // Create a textarea element
                let textArea = document.createElement('textarea');
                textArea.classList = "form-control";
                textArea.rows = "5";
                textArea.innerText = "Your Comment";

                // Create a button div element
                let buttonDiv = document.createElement('div');
                buttonDiv.classList = "btn-list ms-auto mt-2";

                // Create a cancel button element
                let cancelButton = document.createElement('button');
                cancelButton.classList = "btn btn-danger";
                cancelButton.innerText = "Cancel";

                // Create a submit button element
                let submitButton = document.createElement('button');
                submitButton.classList = "btn btn-success ms-3";
                submitButton.innerText = "Submit";

                // Append elements to the comment div
                commentDiv.appendChild(textArea);
                commentDiv.appendChild(buttonDiv);
                buttonDiv.appendChild(cancelButton);
                buttonDiv.appendChild(submitButton);

                // Append the comment div to the parent element
                parentDiv.appendChild(commentDiv);

                // Add a click event listener to the cancel button
                cancelButton.addEventListener('click', () => {
                    parentDiv.removeChild(commentDiv);
                });
            }
        });
    });
}

// Call the replay function to initialize it
replay();
