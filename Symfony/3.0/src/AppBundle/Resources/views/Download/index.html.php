<html>
    <body>
        <table border="0">

<?php
    foreach ($files as $file) {
        echo "<tr>";
        // Is the file a directory or a file
        echo "<td>";
        echo $file['type'];
        echo "</td>";

        // Link to the file or directory
        echo "<td>";
        echo "<a href='" . $file['relativePath'];
        if (is_dir($file['realPath'])) {
            echo "/";
        }
        echo "'>" . $file['relativePath'];
        if (is_dir($file['realPath'])) {
            echo "/";
        }
        echo "</a>";
        echo "</td>";

        // Display size
        echo "<td align='right'>";
        echo $file['fileSize'];
        echo "</td>";
        echo "</tr>";
    }
?>

        </table>
    </body>
</html>
