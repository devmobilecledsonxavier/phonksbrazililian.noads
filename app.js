const YT_NOCOOKIE_BASE = "https://www.youtube-nocookie.com/embed/";
const API_ENDPOINT = "/music.php";

const state = {
  songs: [],
  artists: [],
};

const searchInput = document.getElementById("searchInput");
const resultsList = document.getElementById("resultsList");
const emptyState = document.getElementById("emptyState");
const musicPlayer = document.getElementById("musicPlayer");
const nowPlaying = document.getElementById("nowPlaying");
const apiStatus = document.getElementById("apiStatus");
const artistCount = document.getElementById("artistCount");
const songCount = document.getElementById("songCount");

function isValidYouTubeId(id) {
  return /^[A-Za-z0-9_-]{11}$/.test(id);
}

function buildEmbedUrl(videoId) {
  if (!isValidYouTubeId(videoId)) {
    throw new Error("ID de vídeo inválido.");
  }

  const params = new URLSearchParams({
    rel: "0",
    playsinline: "1"
  });

  return `${YT_NOCOOKIE_BASE}${videoId}?${params.toString()}`;
}

function setStatus(message, isError = false) {
  apiStatus.textContent = message;
  apiStatus.classList.toggle("is-error", isError);
}

function updateStats() {
  artistCount.textContent = String(state.artists.length);
  songCount.textContent = String(state.songs.length);
}

function loadSong(song) {
  try {
    const url = buildEmbedUrl(song.youtubeId);
    musicPlayer.src = url;
    nowPlaying.textContent = `Tocando agora: ${song.title} — ${song.artist}`;
  } catch (error) {
    nowPlaying.textContent = "Não foi possível carregar esta música.";
    console.error(error);
  }
}

function createResultItem(song) {
  const li = document.createElement("li");
  li.className = "result-item";

  const button = document.createElement("button");
  button.type = "button";

  const title = document.createElement("span");
  title.className = "song-title";
  title.textContent = song.title;

  const artist = document.createElement("span");
  artist.className = "song-artist";
  artist.textContent = song.artist;

  button.appendChild(title);
  button.appendChild(artist);

  button.addEventListener("click", () => {
    loadSong(song);
  });

  li.appendChild(button);
  return li;
}

function renderResults(items) {
  resultsList.innerHTML = "";

  if (items.length === 0) {
    emptyState.textContent = "Nenhuma música encontrada.";
    emptyState.style.display = "block";
    return;
  }

  emptyState.style.display = "none";

  items.forEach((song) => {
    resultsList.appendChild(createResultItem(song));
  });
}

function filterSongs(query) {
  const normalized = query.trim().toLowerCase();

  if (!normalized) {
    return state.songs;
  }

  return state.songs.filter((song) => {
    return (
      song.title.toLowerCase().includes(normalized) ||
      song.artist.toLowerCase().includes(normalized)
    );
  });
}

async function fetchMusicDatabase() {
  const response = await fetch(API_ENDPOINT, {
    method: "GET",
    headers: {
      "Accept": "application/json"
    },
    credentials: "same-origin"
  });

  if (!response.ok) {
    throw new Error(`Falha ao carregar o banco musical. Código ${response.status}.`);
  }

  const payload = await response.json();

  if (!payload.success || !Array.isArray(payload.songs) || !Array.isArray(payload.artists)) {
    throw new Error("Estrutura de resposta inválida do backend.");
  }

  const safeSongs = payload.songs.filter((song) => {
    return (
      typeof song?.title === "string" &&
      typeof song?.artist === "string" &&
      typeof song?.youtubeId === "string" &&
      isValidYouTubeId(song.youtubeId)
    );
  });

  state.songs = safeSongs;
  state.artists = payload.artists;
  updateStats();
  renderResults(state.songs);

  if (state.songs.length > 0) {
    loadSong(state.songs[0]);
  }

  const version = payload.meta?.version ? `v${payload.meta.version}` : "versão desconhecida";
  setStatus(`Banco carregado com sucesso (${version}).`);
}

searchInput.addEventListener("input", (event) => {
  const filtered = filterSongs(event.target.value);
  renderResults(filtered);
});

(async function init() {
  try {
    setStatus("Lendo banco de músicas...", false);
    await fetchMusicDatabase();
  } catch (error) {
    renderResults([]);
    nowPlaying.textContent = "O player está aguardando um banco válido.";
    setStatus(error.message || "Erro desconhecido ao carregar o catálogo.", true);
    console.error(error);
  }
})();
