const express = require('express');
const app = express();
const PORT = process.env.PORT || 3000;
const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');
const { createProxyMiddleware } = require('http-proxy-middleware');

app.set("view engine", "ejs");
app.use(express.static('public'));

// Pokreni PHP server interno na portu 8080
const phpServer = spawn('php', ['-S', '0.0.0.0:8080', '-t', 'public']);
phpServer.stderr.on('data', (data) => console.log(`PHP: ${data}`));
phpServer.on('error', (err) => console.log('PHP nije dostupan:', err.message));

// Proslijedi .php zahtjeve na PHP server
app.use((req, res, next) => {
    if (req.path.endsWith('.php')) {
        return createProxyMiddleware({
            target: 'http://localhost:8080',
            changeOrigin: true,
        })(req, res, next);
    }
    next();
});

// Stara Node ruta za slike
app.get('/slike', (req, res) => {
    const dataPath = path.join(__dirname, 'images.json');
    const images = JSON.parse(fs.readFileSync(dataPath, "utf8"));
    res.render('slike', { images });
});

app.listen(PORT, () => {
    console.log(`Server pokrenut na portu ${PORT}`);
});