// Snake — by Bruno Vieira. First built in Java (Swing) back in 2013,
// rebuilt for the browser since.

(function () {
  "use strict";

  // ---- Constants (equivalent to the Grade class) ----
  const WIDTH = 650;
  const HEIGHT = 400;
  const CELL_SIZE = 10;
  const MAX_LENGTH = (WIDTH * HEIGHT) / CELL_SIZE;
  const RANDOM_X_CELLS = 64;
  const RANDOM_Y_CELLS = 39;

  const canvas = document.getElementById("grade");
  const ctx = canvas.getContext("2d");

  // ---- Colors (matches the site's own palette instead of the old pixel-art sprites) ----
  const BG_COLOR = "#0b0e0c";
  const FOOD_COLOR = "#ffb454"; // same orange as the site's own "html file" </> explorer icon
  const FOOD_ICON = "";   // that icon's glyph, from the site's seti icon font
  const FOOD_FONT = "20px 'seti'";
  const SKINS = {
    male: { head: "#ffb454", body: "#c8d1c4" },   // accent orange head, sage-green body
    female: { head: "#8fd19e", body: "#a67638" }, // ok-green head, dim-orange body
  };

  let skin = SKINS.male;

  // ---- Game state ----
  let DELAY = 150;
  const x = new Array(MAX_LENGTH).fill(0);
  const y = new Array(MAX_LENGTH).fill(0);
  let snakeLength = 3;

  let foodX = 0;
  let foodY = 0;

  let SCORE = 0;
  const SCORE_FONT = "bold 13px 'JetBrains Mono', Consolas, monospace";

  let left = false;
  let right = false;
  let up = false;
  let down = false;

  let gameRunning = true;
  let gamePaused = false;
  let wallsBlocked = true;

  let gameTimer = null; // setInterval id (equivalent to javax.swing.Timer)
  let message = "";

  // ---- Game loop ----
  function startGame() {
    snakeLength = 3;
    for (let i = 0; i < snakeLength; i++) {
      x[i] = 50 - i * 10;
      y[i] = 50;
    }

    placeFood();

    if (gameTimer) clearInterval(gameTimer);
    gameTimer = setInterval(gameTick, DELAY);

    console.log("New game started.");
  }

  function startNewGame() {
    if (gameTimer) clearInterval(gameTimer);

    left = false;
    right = false;
    up = false;
    down = false;

    SCORE = 0;
    DELAY = 150;
    gameRunning = true;
    gamePaused = false;

    startGame();
  }

  function chooseSnakeSkin(sex) {
    skin = sex === 1 ? SKINS.female : SKINS.male;
    console.log(sex === 1 ? "Green snake selected." : "Orange snake selected.");
    paint();
  }

  function placeFood() {
    let random = Math.floor(Math.random() * RANDOM_X_CELLS);
    foodX = random * CELL_SIZE;

    random = Math.floor(Math.random() * RANDOM_Y_CELLS);
    foodY = random * CELL_SIZE;
  }

  function checkFood() {
    if (x[0] === foodX && y[0] === foodY) {
      snakeLength++;
      SCORE++;

      const milestones = [9, 12, 15, 18, 20, 22, 25, 30, 32, 35, 40, 45, 50, 60, 70, 80, 90, 100, 150, 200];
      if (milestones.includes(SCORE)) {
        increaseSpeed();
      }

      console.log("-> Ate the fruit.");
      placeFood();
    }
  }

  function increaseSpeed() {
    DELAY--;
    if (gameTimer) clearInterval(gameTimer);
    gameTimer = setInterval(gameTick, DELAY);
    console.log("Speed increased.");
  }

  function lockWalls() {
    wallsBlocked = true;
    console.log("Walls locked.");
    paint();
  }

  function unlockWalls() {
    wallsBlocked = false;
    console.log("Walls unlocked.");
    paint();
  }

  function move() {
    for (let i = snakeLength; i > 0; i--) {
      x[i] = x[i - 1];
      y[i] = y[i - 1];
    }

    if (left) x[0] -= CELL_SIZE;
    if (right) x[0] += CELL_SIZE;
    if (up) y[0] -= CELL_SIZE;
    if (down) y[0] += CELL_SIZE;
  }

  function checkCollision() {
    for (let i = snakeLength; i > 0; i--) {
      if (i > 4 && x[0] === x[i] && y[0] === y[i]) {
        gameRunning = false;
      }
    }

    if (wallsBlocked) {
      if (y[0] > HEIGHT) gameRunning = false;
      if (y[0] < 0) gameRunning = false;
      if (x[0] > WIDTH) gameRunning = false;
      if (x[0] < 0) gameRunning = false;
    } else {
      if (y[0] > HEIGHT) y[0] = 0;
      if (y[0] < 0) y[0] = HEIGHT;
      if (x[0] > WIDTH) x[0] = 0;
      if (x[0] < 0) x[0] = WIDTH;
    }
  }

  function pause() {
    gamePaused = true;
    if (gameTimer) clearInterval(gameTimer);
    console.log("Paused.");
    paint();
  }

  function resume() {
    gamePaused = false;
    gameTimer = setInterval(gameTick, DELAY);
    console.log("Resumed.");
  }

  function gameTick() {
    if (gameRunning) {
      checkFood();
      checkCollision();
      move();
    }
    paint();
  }

  // ---- Drawing (equivalent to paint(Graphics)) ----
  function paint() {
    ctx.clearRect(0, 0, WIDTH, HEIGHT);

    if (gameRunning) {
      ctx.fillStyle = BG_COLOR;
      ctx.fillRect(0, 0, WIDTH, HEIGHT);
      drawFood();

      for (let i = 0; i < snakeLength; i++) {
        ctx.fillStyle = i === 0 ? skin.head : skin.body;
        ctx.fillRect(x[i], y[i], CELL_SIZE, CELL_SIZE);
      }

      drawScore();
      drawWallsIndicator();
      drawPauseOverlay();
    } else {
      drawGameOver();
    }
  }

  function drawFood() {
    ctx.fillStyle = FOOD_COLOR;
    ctx.font = FOOD_FONT;
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillText(FOOD_ICON, foodX + CELL_SIZE / 2, foodY + CELL_SIZE / 2 + 1);
    ctx.textAlign = "left";
    ctx.textBaseline = "alphabetic";
  }

  function drawWallsIndicator() {
    if (!wallsBlocked) return;
    ctx.strokeStyle = "#ffb454";
    ctx.lineWidth = 3;
    ctx.strokeRect(1.5, 1.5, WIDTH - 3, HEIGHT - 3);
  }

  function drawScore() {
    const SCORE_TEXT = "SCORE: " + SCORE;
    ctx.fillStyle = "white";
    ctx.font = SCORE_FONT;
    ctx.textBaseline = "alphabetic";
    const w = ctx.measureText(SCORE_TEXT).width;
    ctx.fillText(SCORE_TEXT, WIDTH - w - 10, HEIGHT - 10);
  }

  function drawPauseOverlay() {
    if (!gamePaused) return;

    ctx.fillStyle = "white";
    ctx.textAlign = "center";

    ctx.font = "bold 40px 'JetBrains Mono', Consolas, monospace";
    ctx.fillText("PAUSED", WIDTH / 2, HEIGHT / 2);

    ctx.font = "bold 15px 'JetBrains Mono', Consolas, monospace";
    ctx.fillText("CTRL + R to resume :)", WIDTH / 2, HEIGHT / 2 + 25);

    ctx.textAlign = "left";
  }

  function drawGameOver() {
    ctx.fillStyle = BG_COLOR;
    ctx.fillRect(0, 0, WIDTH, HEIGHT);

    if (SCORE < 10) {
      message = "Already?? Your score: " + SCORE;
    } else if (SCORE < 50) {
      message = "Practice more, Ms. Snake! Your score: " + SCORE;
    } else if (SCORE < 100) {
      message = "Hmmm... good score! Your score: " + SCORE;
    } else if (SCORE < 200) {
      message = "Ooh, getting good! Your score: " + SCORE;
    } else if (SCORE < 250) {
      message = "You're a beast!!! Your score: " + SCORE;
    } else {
      message = "Congrats!! You're the best! Your score: " + SCORE;
    }

    ctx.fillStyle = "white";
    ctx.font = "bold 14px 'JetBrains Mono', Consolas, monospace";
    ctx.textAlign = "center";
    ctx.fillText(message, WIDTH / 2, HEIGHT / 2);
    ctx.textAlign = "left";
  }

  // ---- Keyboard (equivalent to the TAdapter class) ----
  window.addEventListener("keydown", (e) => {
    switch (e.code) {
      case "ArrowLeft":
        if (!right) {
          left = true;
          up = false;
          down = false;
          console.log("-> Left");
        }
        e.preventDefault();
        break;
      case "ArrowRight":
        if (!left) {
          right = true;
          up = false;
          down = false;
          console.log("-> Right");
        }
        e.preventDefault();
        break;
      case "ArrowUp":
        if (!down) {
          up = true;
          left = false;
          right = false;
          console.log("-> Up");
        }
        e.preventDefault();
        break;
      case "ArrowDown":
        if (!up) {
          down = true;
          left = false;
          right = false;
          console.log("-> Down");
        }
        e.preventDefault();
        break;
      case "Escape":
        exitGame();
        break;
      case "KeyP":
        if (e.ctrlKey || e.metaKey) { e.preventDefault(); pause(); }
        break;
      case "KeyR":
        if (e.ctrlKey || e.metaKey) { e.preventDefault(); resume(); }
        break;
      case "KeyM":
        if (e.ctrlKey || e.metaKey) { e.preventDefault(); chooseSnakeSkin(0); }
        break;
      case "KeyF":
        if (e.ctrlKey || e.metaKey) { e.preventDefault(); chooseSnakeSkin(1); }
        break;
      // Ctrl+N and Ctrl+T are reserved by the browser and can't be
      // intercepted; use the corresponding menu items instead.
    }
  });

  // ---- Generic modal (equivalent to JOptionPane) ----
  const overlay = document.getElementById("modal-overlay");
  const modalTitle = document.getElementById("modal-title");
  const modalText = document.getElementById("modal-text");
  const modalButtons = document.getElementById("modal-buttons");

  function showMessage(title, text) {
    modalTitle.textContent = title;
    modalText.textContent = text;
    modalButtons.innerHTML = "";
    const ok = document.createElement("button");
    ok.textContent = "OK";
    ok.autofocus = true;
    ok.onclick = closeModal;
    modalButtons.appendChild(ok);
    overlay.classList.remove("hidden");
    ok.focus();
  }

  function showConfirm(title, text, onYes) {
    modalTitle.textContent = title;
    modalText.textContent = text;
    modalButtons.innerHTML = "";

    const yes = document.createElement("button");
    yes.textContent = "Yes";
    yes.onclick = () => { closeModal(); onYes(); };

    const no = document.createElement("button");
    no.textContent = "No";
    no.onclick = closeModal;

    modalButtons.appendChild(yes);
    modalButtons.appendChild(no);
    overlay.classList.remove("hidden");
    yes.focus();
  }

  function closeModal() {
    overlay.classList.add("hidden");
  }

  // ---- Menu actions (equivalent to the Menu class) ----
  function manual() {
    showMessage(
      "How to Play",
      "Controls:\n\n" +
      "* Arrow keys - UP, DOWN, LEFT and RIGHT.\n\n" +
      "How to Play:\n\n" +
      "* Try to eat as many fruits as possible and rack up points,\n" +
      "while your little snake keeps growing with every bite.\n\n" +
      "* Use the arrow keys to move the snake.\n\n" +
      "* Pause the game at any time with CTRL + P\n\n" +
      "Good luck, little snake!"
    );
  }

  function aboutDeveloper() {
    showMessage(
      "About the Developer:",
      "Bruno Vieira\n\n" +
      "www.brunovidasi.com | bruno@brunovidasi.com\n\n" +
      "ID @brunovidasi 2012.01.74693-1\n\n" +
      "Built this back in 10/2013 — still one of my favourites."
    );
  }

  function exitGame() {
    showConfirm("Exit", "Are you sure you want to exit?", () => {
      if (gameTimer) clearInterval(gameTimer);
      showMessage("Game ended", "You can safely close this tab.");
    });
  }

  // ---- Menu item wiring (HTML) ----
  const actions = {
    newGame: startNewGame,
    pause: pause,
    resume: resume,
    exit: exitGame,
    lockWalls: lockWalls,
    unlockWalls: unlockWalls,
    orangeSnake: () => chooseSnakeSkin(0),
    greenSnake: () => chooseSnakeSkin(1),
    manual: manual,
    developer: aboutDeveloper,
  };

  document.querySelectorAll("[data-action]").forEach((btn) => {
    btn.addEventListener("click", () => {
      closeMenus();
      const action = actions[btn.dataset.action];
      if (action) action();
    });
  });

  // Dropdown menus (equivalent to JMenuBar's behavior).
  const menus = document.querySelectorAll(".menu");
  menus.forEach((menu) => {
    const title = menu.querySelector(".menu-title");
    title.addEventListener("click", (e) => {
      e.stopPropagation();
      const wasOpen = menu.classList.contains("open");
      closeMenus();
      if (!wasOpen) menu.classList.add("open");
    });
  });
  document.addEventListener("click", closeMenus);

  function closeMenus() {
    menus.forEach((m) => m.classList.remove("open"));
  }

  // ---- Confirm before closing the tab (equivalent to WindowListener) ----
  window.addEventListener("beforeunload", (e) => {
    e.preventDefault();
    e.returnValue = "";
  });

  // ---- Initialization ----
  function start() {
    chooseSnakeSkin(0);
    startGame();
    paint();
  }

  // the food glyph comes from a custom icon font — make sure it's actually
  // loaded before the first paint, or it silently falls back to a blank glyph
  if (document.fonts && document.fonts.load) {
    document.fonts.load(FOOD_FONT).catch(() => {}).then(start);
  } else {
    start();
  }
})();
