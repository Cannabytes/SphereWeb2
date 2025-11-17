(function () {
  "use strict";

  var myElement11 = document.getElementById("mail-main-nav");
  if (myElement11) {
    new SimpleBar(myElement11, { autoHide: true });
  }

  var myElement12 = document.getElementById("mail-messages");
  if (myElement12) {
    new SimpleBar(myElement12, { autoHide: true });
  }

  var myElement14 = document.getElementById("mail-recepients");
  if (myElement14) {
    new SimpleBar(myElement14, { autoHide: true });
  }

  /* mail editor */
  var toolbarOptions = [
    [{ header: [1, 2, 3, 4, 5, 6, false] }],
    [{ font: [] }],
    ["bold", "italic", "underline", "strike"], // toggled buttons
    ["blockquote", "code-block"],

    [{ header: 1 }, { header: 2 }], // custom button values
    [{ list: "ordered" }, { list: "bullet" }],

    [{ color: [] }, { background: [] }], // dropdown with defaults from theme
    [{ align: [] }],

    ["image", "video"],
    ["clean"], // remove formatting button
  ];

  var quill1 = new Quill("#mail-compose-editor", {
    modules: {
      toolbar: toolbarOptions,
    },
    theme: "snow",
  });

  /* to choices js */
  const multipleCancelButton = new Choices("#toMail", {
    allowHTML: true,
    removeItemButton: true,
  });

})();
