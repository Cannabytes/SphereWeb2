(function () {
    'use script';

    // basic example
    new gridjs.Grid({
        resizable: true,
        columns: [{
            name: "Date",
            width: "150px",
        }, {
            name: "Name",
            width: "150px",
        }, {
            name: "Email",
            width: "200px",
        }, {
            name: "ID",
            width: "150px",
        }, {
            name: "Price",
            width: "100px",
        }, {
            name: "Quantity",
            width: "100px",
        }, {
            name: "Total",
            width: "100px",
        }],
        data: [
            ["24-10-2022 12:47", "john", "john123@gmail.com", "#12012", "$1799", "1", "$1799"],
            ["12-09-2022 04:24", "mark", "markzenner23@gmail.com", "#12013", "$2479", "2", "$4958"],
            ["18-11-2022 18:43", "eoin", "eoin1992@gmail.com", "#12014", "$769", "1", "$769"],
            ["10-09-2022 10:35", "sarahcdd", "sarahcdd129@gmail.com", "#12015", "$1299", "3", "$3997"],
            ["27-10-2022 09:55", "afshin", "afshin@example.com", "#12016", "$1449", "1", "$1449"]
        ],
    }).render(document.getElementById("grid-example1"));
    // basic example

    // with pagination
    new gridjs.Grid({
        pagination: true,
        columns: [{
            name: "Date",
            width: "150px",
        }, {
            name: "Name",
            width: "150px",
        }, {
            name: "Email",
            width: "200px",
        }, {
            name: "ID",
            width: "150px",
        }, {
            name: "Price",
            width: "100px",
        }, {
            name: "Quantity",
            width: "100px",
        }, {
            name: "Total",
            width: "100px",
        }],
        data: [
            ["24-10-2022 12:47", "john", "john123@gmail.com", "#12012", "$1799", "1", "$1799"],
            ["12-09-2022 04:24", "mark", "markzenner23@gmail.com", "#12013", "$2479", "2", "$4958"],
            ["18-11-2022 18:43", "eoin", "eoin1992@gmail.com", "#12014", "$769", "1", "$769"],
            ["10-09-2022 10:35", "sarahcdd", "sarahcdd129@gmail.com", "#12015", "$1299", "3", "$3997"],
            ["27-10-2022 09:55", "afshin", "afshin@example.com", "#12016", "$1449", "1", "$1449"]
        ],
    }).render(document.getElementById("grid-pagination"));;
    // with pagination

    // with search
    new gridjs.Grid({
        pagination: true,
        search: true,
        columns: [{
            name: "Date",
            width: "150px",
        }, {
            name: "Name",
            width: "150px",
        }, {
            name: "Email",
            width: "200px",
        }, {
            name: "ID",
            width: "150px",
        }, {
            name: "Price",
            width: "100px",
        }, {
            name: "Quantity",
            width: "100px",
        }, {
            name: "Total",
            width: "100px",
        }],
        data: [
            ["24-10-2022 12:47", "john", "john123@gmail.com", "#12012", "$1799", "1", "$1799"],
            ["12-09-2022 04:24", "mark", "markzenner23@gmail.com", "#12013", "$2479", "2", "$4958"],
            ["18-11-2022 18:43", "eoin", "eoin1992@gmail.com", "#12014", "$769", "1", "$769"],
            ["10-09-2022 10:35", "sarahcdd", "sarahcdd129@gmail.com", "#12015", "$1299", "3", "$3997"],
            ["27-10-2022 09:55", "afshin", "afshin@example.com", "#12016", "$1449", "1", "$1449"]
        ],
    }).render(document.getElementById("grid-search"));;
    // with search

    // with sorting
    new gridjs.Grid({
        pagination: true,
        search: true,
        sort: true,
        columns: [{
            name: "Date",
            width: "150px",
        }, {
            name: "Name",
            width: "150px",
        }, {
            name: "Email",
            width: "200px",
        }, {
            name: "ID",
            width: "150px",
        }, {
            name: "Price",
            width: "100px",
        }, {
            name: "Quantity",
            width: "100px",
        }, {
            name: "Total",
            width: "100px",
        }],
        data: [
            ["24-10-2022 12:47", "john", "john123@gmail.com", "#12012", "$1799", "1", "$1799"],
            ["12-09-2022 04:24", "mark", "markzenner23@gmail.com", "#12013", "$2479", "2", "$4958"],
            ["18-11-2022 18:43", "eoin", "eoin1992@gmail.com", "#12014", "$769", "1", "$769"],
            ["10-09-2022 10:35", "sarahcdd", "sarahcdd129@gmail.com", "#12015", "$1299", "3", "$3997"],
            ["27-10-2022 09:55", "afshin", "afshin@example.com", "#12016", "$1449", "1", "$1449"]
        ],
    }).render(document.getElementById("grid-sorting"));;
    // with sorting

    // loading state
    new gridjs.Grid({
        columns: [{
            name: "Date",
            width: "150px",
        }, {
            name: "Name",
            width: "150px",
        }, {
            name: "Email",
            width: "200px",
        }, {
            name: "ID",
            width: "150px",
        }, {
            name: "Price",
            width: "100px",
        }, {
            name: "Quantity",
            width: "100px",
        }, {
            name: "Total",
            width: "100px",
        }],
        pagination: true,
        search: true,
        sort: true,
        data: () => {
            return new Promise(resolve => {
                setTimeout(() =>
                    resolve([
                        ["24-10-2022 12:47", "john", "john123@gmail.com", "#12012", "$1799", "1", "$1799"],
                        ["12-09-2022 04:24", "mark", "markzenner23@gmail.com", "#12013", "$2479", "2", "$4958"],
                        ["18-11-2022 18:43", "eoin", "eoin1992@gmail.com", "#12014", "$769", "1", "$769"],
                        ["10-09-2022 10:35", "sarahcdd", "sarahcdd129@gmail.com", "#12015", "$1299", "3", "$3997"],
                        ["27-10-2022 09:55", "afshin", "afshin@example.com", "#12016", "$1449", "1", "$1449"]
                    ]), 2000);
            });
        }
    }).render(document.getElementById("grid-loading"));
    // loading state

    //wide tables
    new gridjs.Grid({
        columns: [{
            name: "Date",
            width: "150px",
        }, {
            name: "Name",
            width: "150px",
        }, {
            name: "Email",
            width: "200px",
        }, {
            name: "Order ID",
            width: "150px",
        }, {
            name: "Product",
            width: "150px",
        }, {
            name: "Category",
            width: "150px",
        }, {
            name: "Price",
            width: "100px",
        }, {
            name: "Quantity",
            width: "100px",
        }, {
            name: "Total",
            width: "100px",
        }],
        style: {
            table: {
                'white-space': 'nowrap'
            }
        },
        resizable: true,
        sort: true,
        pagination: true,
        data: [
            ["24-10-2022 12:47", "john", "john123@gmail.com", "#12012", "smart watch", "electronics", "$1799", "1", "$1799"],
            ["12-09-2022 04:24", "mark", "markzenner23@gmail.com", "#12013", "blue Jeans", "clothing", "$2479", "2", "$4958"],
            ["18-11-2022 18:43", "eoin", "eoin1992@gmail.com", "#12014", "g phone", "mobiles", "$769", "1", "$769"],
            ["10-09-2022 10:35", "sarahcdd", "sarahcdd129@gmail.com", "#12015", "head phones", "electronics", "$1299", "3", "$3997"],
            ["27-10-2022 09:55", "afshin", "afshin@example.com", "#12016", "chair", "furniture", "$1449", "1", "$1449"]
        ],
    }).render(document.getElementById("grid-wide"));
    //wide tables

    // fixed header
    new gridjs.Grid({
        pagination: true,
        search: true,
        sort: true,
        fixedHeader: true,
        height: '350px',
        columns: [{
            name: "Date",
            width: "150px",
        }, {
            name: "Name",
            width: "150px",
        }, {
            name: "Email",
            width: "200px",
        }, {
            name: "ID",
            width: "150px",
        }, {
            name: "Price",
            width: "100px",
        }, {
            name: "Quantity",
            width: "100px",
        }, {
            name: "Total",
            width: "100px",
        }],
        data: [
            ["24-10-2022 12:47", "john", "john123@gmail.com", "#12012", "$1799", "1", "$1799"],
            ["12-09-2022 04:24", "mark", "markzenner23@gmail.com", "#12013", "$2479", "2", "$4958"],
            ["18-11-2022 18:43", "eoin", "eoin1992@gmail.com", "#12014", "$769", "1", "$769"],
            ["10-09-2022 10:35", "sarahcdd", "sarahcdd129@gmail.com", "#12015", "$1299", "3", "$3997"],
            ["27-10-2022 09:55", "afshin", "afshin@example.com", "#12016", "$1449", "1", "$1449"],
            ["24-10-2022 12:47", "john", "john123@gmail.com", "#12012", "$1799", "1", "$1799"],
            ["12-09-2022 04:24", "mark", "markzenner23@gmail.com", "#12013", "$2479", "2", "$4958"],
            ["18-11-2022 18:43", "eoin", "eoin1992@gmail.com", "#12014", "$769", "1", "$769"],
            ["10-09-2022 10:35", "sarahcdd", "sarahcdd129@gmail.com", "#12015", "$1299", "3", "$3997"],
            ["27-10-2022 09:55", "afshin", "afshin@example.com", "#12016", "$1449", "1", "$1449"],
            ["24-10-2022 12:47", "john", "john123@gmail.com", "#12012", "$1799", "1", "$1799"]
        ],
    }).render(document.getElementById("grid-header-fixed"));
    // fixed header

    // hidden columns
    new gridjs.Grid({
        columns: [{
            name: "Date",
            hidden: true,
        }, {
            name: "Name",
            width: "150px",
        }, {
            name: "Email",
            width: "200px",
        }, {
            name: "ID",
            width: "150px",
        }, {
            name: "Price",
            width: "100px",
        }, {
            name: "Quantity",
            width: "100px",
        }, {
            name: "Total",
            width: "100px",
        }],
        sort: true,
        search: true,
        pagination: true,
        data: [
            ["24-10-2022 12:47", "john", "john123@gmail.com", "#12012", "$1799", "1", "$1799"],
            ["12-09-2022 04:24", "mark", "markzenner23@gmail.com", "#12013", "$2479", "2", "$4958"],
            ["18-11-2022 18:43", "eoin", "eoin1992@gmail.com", "#12014", "$769", "1", "$769"],
            ["10-09-2022 10:35", "sarahcdd", "sarahcdd129@gmail.com", "#12015", "$1299", "3", "$3997"],
            ["27-10-2022 09:55", "afshin", "afshin@example.com", "#12016", "$1449", "1", "$1449"]
        ],
    }).render(document.getElementById("grid-hidden-column"));;
    // hidden columns

})();