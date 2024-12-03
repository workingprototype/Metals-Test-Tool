<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Receipt</title>
    <style>
         /* Custom styles for the printed receipt */
         @media print {
            body * {
                visibility: hidden;
            }

            /* Show only the receipt content for printing */
            #receiptContent, #receiptContent * {
                visibility: visible;
            }

            /* Adjust the size and margin to fit the receipt paper */
            #receiptContent {
                width: 210mm; /* Adjust to your paper width */
                height: 148mm; /* Adjust to your paper height */
                position: relative;
                background-image: url('./receipt-format.png'); /* Use the uploaded image as background */
                background-size: cover;
                margin: 0;
                padding: 0;
                font-family: "Courier", monospace;
                font-size: 12px;
                line-height: 1.5;
            }

            /* Positioning the form data to match the pre-printed areas */
            .serial-number {
                position: absolute;
                top: 40mm; /* Adjust the top position */
                left: 25mm; /* Adjust the left position */
            }

            .name {
                position: absolute;
                top: 50mm; /* Adjust the top position */
                left: 25mm; /* Adjust the left position */
            }

            .weight {
                position: absolute;
                top: 40mm; /* Adjust the top position */
                left: 100mm; /* Adjust the left position */
            }

            .sample {
                position: absolute;
                top: 50mm; /* Adjust the top position */
                left: 100mm; /* Adjust the left position */
            }

            .date {
                position: absolute;
                top: 40mm; /* Adjust the top position */
                left: 150mm; /* Adjust the left position */
            }

            .gold {
                position: absolute;
                top: 90mm; /* Adjust the top position */
                left: 25mm; /* Adjust the left position */
            }

            /* Add more positioning rules for other fields as necessary */
        }
    </style>
</head>
<body>

     <!-- Your form goes here -->
     <form id="receiptForm">
        <label for="name">Name:</label><br>
        <input type="text" id="name" name="name" value="John Doe"><br><br>

        <label for="serial">Serial No.:</label><br>
        <input type="text" id="serial" name="serial" value="12345"><br><br>
        
        <label for="weight">Weight:</label><br>
        <input type="text" id="weight" name="weight" value="10g"><br><br>

        <label for="sample">Sample:</label><br>
        <input type="text" id="sample" name="sample" value="Gold"><br><br>

        <label for="date">Date:</label><br>
        <input type="text" id="date" name="date" value="2024-12-01"><br><br>

        <button type="button" onclick="printReceipt()">Print Receipt</button>
    </form>

    <div id="receiptContent" class="receipt" style="display:none;"></div>


   <script>
        // JavaScript to handle the printing
        function printReceipt() {
            // Get form values
            var name = document.getElementById('name').value;
            var serial = document.getElementById('serial').value;
            var weight = document.getElementById('weight').value;
            var sample = document.getElementById('sample').value;
            var date = document.getElementById('date').value;

            // Display form values in hidden div for printing
            var receiptHtml = `
                <div class="serial-number">${serial}</div>
                <div class="name">${name}</div>
                <div class="weight">${weight}</div>
                <div class="sample">${sample}</div>
                <div class="date">${date}</div>
            `;

            document.getElementById('receiptContent').innerHTML = receiptHtml;
            document.getElementById('receiptContent').style.display = 'block';

            // Trigger the print dialog
            window.print();

            // Hide the receipt content after printing
            document.getElementById('receiptContent').style.display = 'none';
        }
    </script>
</body>
</html>