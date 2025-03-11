<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Customers</title>
</head>
<body>
    <h2>Select an Excel File to Import</h2>
    <form action="#" method="POST" enctype="multipart/form-data" id="importForm">
        @csrf
        <input type="file" name="file" required>
        <select name="method">
            <option value="spatie">Spatie Simple Excel</option>
            <option value="laravel-excel">Laravel Excel</option>
            <option value="fast-excel">Fast Excel</option>
            <option value="openspout">OpenSpout</option>
        </select>
        <button type="submit">Import</button>
        <button type="button" id="countRowsBtn">Count Rows</button>
    </form>

    <h3>Import Result</h3>
    <p id="result"></p>

    <script>
        document.getElementById("importForm").addEventListener("submit", function(event) {
            event.preventDefault();
            let formData = new FormData(this);
            let method = formData.get("method");
            let url = "/import/" + method;

            fetch(url, {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById("result").innerText =
                    `Library: ${data.library}\nRows: ${data.rows}\nTime: ${data.time} sec\nMemory: ${data.memory} bytes`;
            })
            .catch(error => console.error("Error:", error));
        });

        document.getElementById("countRowsBtn").addEventListener("click", function() {
            let formData = new FormData(document.getElementById("importForm"));
            let method = formData.get("method");
            let url = "/count-rows/" + method;

            fetch(url, {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById("result").innerText =
                    `Library: ${data.library}\nTotal Rows: ${data.rows}\nTime: ${data.time} sec\nMemory: ${data.memory} bytes`;
            })
            .catch(error => console.error("Error:", error));
        });
    </script>
</body>
</html>
