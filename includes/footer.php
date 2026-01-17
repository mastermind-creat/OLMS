</div> <!-- End Main Content -->

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Scripts -->
<script>
    // Example: Toggle Sidebar on mobile (to be implemented if needed)

    // Global Print Table Function
    function printTable(tableId) {
        const table = document.getElementById(tableId);
        if (!table) {
            alert("Table not found!");
            return;
        }
        
        // Clone table to avoid modifying original
        const tableClone = table.cloneNode(true);
        
        // Remove Action columns if any (identified by 'th:last-child' usually, or specific class)
        // For simplicity, we print as is, but you could refine this.
        
        const newWindow = window.open('', '', 'width=900,height=600');
        newWindow.document.write('<html><head><title>Print Table</title>');
        newWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');
        newWindow.document.write('<style>body { padding: 20px; } table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; }</style>');
        newWindow.document.write('</head><body>');
        newWindow.document.write('<h3>Library Management System Report</h3>');
        newWindow.document.write(tableClone.outerHTML);
        newWindow.document.write('</body></html>');
        newWindow.document.close();
        newWindow.focus();
        setTimeout(() => {
            newWindow.print();
            newWindow.close();
        }, 500);
    }

    // Global Export to Excel Function
    function exportTableToExcel(tableId, filename = 'report') {
        const table = document.getElementById(tableId);
        if (!table) {
            alert("Table not found!");
            return;
        }
        const wb = XLSX.utils.table_to_book(table, {sheet: "Sheet1"});
        XLSX.writeFile(wb, filename + '.xlsx');
    }
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

</body>
</html>
