const express = require('express');
const app = express();
const PORT = process.env.PORT || 3000;
const fs = require('fs');
const path = require('path');

app.set("view engine", "ejs");
app.use(express.static('public'));
app.get('/slike', (req, res) => {
    const dataPath = path.join(__dirname, 'images.json');
    const images = JSON.parse(fs.readFileSync(dataPath, "utf8"));

    res.render('slike', { images });
});
app.listen(PORT, () => {
    console.log('Server pokrenut na portu ${PORT}');
});