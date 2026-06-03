
let sviFilmovi = [];   // globalna varijabla - dostupna svuda
let kosarica = [];   // globalna košarica

// ── ZADATAK 1: Dohvat i prikaz podataka ─────────────────────
fetch('movies.csv')
    .then(res => res.text())
    .then(csv => {
        const rezultat = Papa.parse(csv, {
            header: true,
            skipEmptyLines: true
        });

        // Mapiramo svaki redak u objekt s ispravnim tipovima
        sviFilmovi = rezultat.data.map(film => ({
            naslov: film.Naslov || '',
            zanr: film.Zanr || '',
            godina: Number(film.Godina) || 0,
            trajanje: Number(film.Trajanje_min) || 0,
            ocjena: Number(film.Ocjena) || 0,
            redatelj: film.Rezisery || '',
            zemlja: film.Zemlja_porijekla || ''
        }));

        // Prikaži prvih 30 u statičnoj tablici (Zadatak 1)
        prikaziZad1(sviFilmovi.slice(0, 30));

        // Popuni dropdown za žanr dinamički
        popuniZanrove();

        // Inicijalni prikaz u filtriranoj tablici (Zadatak 2)
        prikaziFiltrirane(sviFilmovi.slice(0, 30));
    })
    .catch(err => console.error('Greška pri dohvatu CSV-a:', err));


// ── ZADATAK 1: Prikaz u tablici ──────────────────────────────
function prikaziZad1(filmovi) {
    const tbody = document.querySelector('#zad1-tablica tbody');
    tbody.innerHTML = '';

    for (const film of filmovi) {
        const row = document.createElement('tr');
        row.innerHTML = `
      <td>${film.naslov}</td>
      <td>${film.zanr}</td>
      <td>${film.godina}</td>
      <td>${film.trajanje} min</td>
      <td>${film.redatelj}</td>
      <td><span class="ocjena-badge">${film.ocjena.toFixed(1)}</span></td>
    `;
        tbody.appendChild(row);
    }
}


// ── ZADATAK 2: Filtriranje ───────────────────────────────────

// Dinamički popuni dropdown žanrova iz podataka
function popuniZanrove() {
    const select = document.getElementById('filter-zanr');
    // Skupi sve jedinstvene žanrove (neki imaju više žanrova odvojenih zarezom)
    const sviZanrovi = new Set();
    sviFilmovi.forEach(film => {
        film.zanr.split(',').forEach(z => sviZanrovi.add(z.trim()));
    });
    [...sviZanrovi].sort().forEach(z => {
        const opt = document.createElement('option');
        opt.value = z;
        opt.textContent = z;
        select.appendChild(opt);
    });
}

// Ažuriraj prikaz vrijednosti slidera za ocjenu
document.getElementById('filter-ocjena').addEventListener('input', function () {
    document.getElementById('ocjena-vrijednost').textContent = Number(this.value).toFixed(1);
});

// Ažuriraj prikaz vrijednosti slidera za godinu
document.getElementById('filter-godina-do').addEventListener('input', function () {
    document.getElementById('godina-do-vrijednost').textContent = this.value;
});

// Klik na gumb "Filtriraj"
document.getElementById('btn-filtriraj').addEventListener('click', filtriraj);

function filtriraj() {
    const zanr = document.getElementById('filter-zanr').value;
    const godinaOd = parseInt(document.getElementById('filter-godina-od').value) || 0;
    const godinaDo = parseInt(document.getElementById('filter-godina-do').value) || 9999;
    const minOcjena = parseFloat(document.getElementById('filter-ocjena').value) || 0;

    const filtrirani = sviFilmovi.filter(film => {
        const zanrMatch = !zanr || film.zanr.toLowerCase().includes(zanr.toLowerCase());
        const godinaMatch = film.godina >= godinaOd && film.godina <= godinaDo;
        const ocjenaMatch = film.ocjena >= minOcjena;
        return zanrMatch && godinaMatch && ocjenaMatch;
    });

    prikaziFiltrirane(filtrirani);
}

function prikaziFiltrirane(filmovi) {
    const tbody = document.querySelector('#zad2-tablica tbody');
    tbody.innerHTML = '';

    if (filmovi.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="prazno">Nema filmova za odabrane filtere.</td></tr>';
        return;
    }

    for (const film of filmovi) {
        const row = document.createElement('tr');
        row.innerHTML = `
      <td>${film.naslov}</td>
      <td>${film.zanr}</td>
      <td>${film.godina}</td>
      <td>${film.trajanje} min</td>
      <td>${film.zemlja}</td>
      <td><span class="ocjena-badge">${film.ocjena.toFixed(1)}</span></td>
      <td><button class="btn-dodaj" onclick="dodajUKosaricu('${film.naslov.replace(/'/g, "\\'")}')">+ Dodaj</button></td>
    `;
        tbody.appendChild(row);
    }
}


// ── ZADATAK 3: Košarica ──────────────────────────────────────

function dodajUKosaricu(naslov) {
    const film = sviFilmovi.find(f => f.naslov === naslov);
    if (!film) return;

    if (kosarica.some(f => f.naslov === naslov)) {
        prikaziObavijest('Film je već u košarici!', 'warn');
        return;
    }

    kosarica.push(film);
    osvjeziKosaricu();
    prikaziObavijest(`"${film.naslov}" dodano u košaricu!`, 'ok');
}

function ukloniIzKosarice(index) {
    kosarica.splice(index, 1);
    osvjeziKosaricu();
}

function osvjeziKosaricu() {
    // Broj u badge-u
    const count = document.getElementById('kosarica-count');
    count.textContent = kosarica.length;
    count.style.display = kosarica.length > 0 ? 'inline-block' : 'none';

    // Lista filmova u aside-u
    const lista = document.getElementById('kosarica-lista');
    lista.innerHTML = '';

    if (kosarica.length === 0) {
        lista.innerHTML = '<li class="kosarica-prazna">Košarica je prazna.</li>';
        return;
    }

    kosarica.forEach((film, i) => {
        const li = document.createElement('li');
        li.innerHTML = `
      <div class="kosarica-film-info">
        <span class="kosarica-naslov">${film.naslov}</span>
        <span class="kosarica-meta">${film.godina} · ${film.zanr}</span>
      </div>
      <button class="btn-ukloni" onclick="ukloniIzKosarice(${i})">✕</button>
    `;
        lista.appendChild(li);
    });
}

// Potvrda košarice
document.getElementById('btn-potvrdi').addEventListener('click', () => {
    if (kosarica.length === 0) {
        prikaziObavijest('Košarica je prazna!', 'warn');
        return;
    }
    const poruka = `Uspješno ste dodali ${kosarica.length} ${kosarica.length === 1 ? 'film' : 'filma/filmova'} u košaricu za vikend maraton! 🎬`;
    prikaziObavijest(poruka, 'ok');
    kosarica = [];
    osvjeziKosaricu();
});

// Toggle košarica (otvori/zatvori)
document.getElementById('btn-kosarica-toggle').addEventListener('click', () => {
    document.getElementById('kosarica-aside').classList.toggle('otvorena');
});

// ── Pomoćna: toast obavijest ─────────────────────────────────
function prikaziObavijest(poruka, tip = 'ok') {
    const toast = document.getElementById('toast');
    toast.textContent = poruka;
    toast.className = 'toast ' + tip + ' vidljiv';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => {
        toast.classList.remove('vidljiv');
    }, 3000);
}