
(function () {
  "use strict";

  const WIDTH = 650;
  const HEIGHT = 400;
  const CELL_SIZE = 10;
  const MAX_LENGTH = (WIDTH * HEIGHT) / CELL_SIZE;
  const RANDOM_X_CELLS = 64;
  const RANDOM_Y_CELLS = 39;

  const canvas = document.getElementById("grade");
  const ctx = canvas.getContext("2d");

  const BG_COLOR = "#0b0e0c";
  const FOOD_ICONS = [
    { char: "", color: "#cbcb41" },
    { char: "", color: "#ffb454" },
    { char: "", color: "#cbcb41" },
    { char: "", color: "#519aba" },
    { char: "", color: "#ffb454" },
    { char: "", color: "#a074c4" },
    { char: "", color: "#8dc149" },
    { char: "", color: "#cc3e44" },
    { char: "", color: "#519aba" },
  ];
  const FOOD_FONT = "20px 'seti'";
  const HTML_ICON = FOOD_ICONS[4];
  let food = HTML_ICON;
  const SKINS = {
    male: { head: "#ffb454", body: "#c8d1c4" },
    female: { head: "#8fd19e", body: "#a67638" },
  };

  let skin = SKINS.male;

  let baseDelay = 150;
  let DELAY = baseDelay;
  const x = new Array(MAX_LENGTH).fill(0);
  const y = new Array(MAX_LENGTH).fill(0);
  let snakeLength = 3;

  let foodX = 0;
  let foodY = 0;

  let SCORE = 0;
  let level = 1;

  let left = false;
  let right = false;
  let up = false;
  let down = false;
  let queuedDirection = null;
  const OPPOSITES = { left: "right", right: "left", up: "down", down: "up" };

  let gameRunning = true;
  let gamePaused = false;
  let wallsBlocked = false;

  let gameTimer = null;
  let message = "";

  function startGame() {
    snakeLength = 3;
    for (let i = 0; i < snakeLength; i++) {
      x[i] = 50 - i * 10;
      y[i] = 50;
    }

    placeFood(false);

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
    queuedDirection = null;

    SCORE = 0;
    DELAY = baseDelay;
    level = 1;
    gameRunning = true;
    gamePaused = false;

    startGame();
  }

  function newGameWithDifficulty(delay) {
    baseDelay = delay;
    startNewGame();
  }

  function chooseSnakeSkin(sex) {
    skin = sex === 1 ? SKINS.female : SKINS.male;
    console.log(sex === 1 ? "Green snake selected." : "Orange snake selected.");
    updateEditMenuChecks();
    paint();
  }

  function placeFood(randomizeIcon) {
    do {
      const randomX = Math.floor(Math.random() * RANDOM_X_CELLS);
      foodX = randomX * CELL_SIZE;

      const randomY = Math.floor(Math.random() * RANDOM_Y_CELLS);
      foodY = randomY * CELL_SIZE;
    } while (isOnSnake(foodX, foodY));

    food = randomizeIcon ? FOOD_ICONS[Math.floor(Math.random() * FOOD_ICONS.length)] : HTML_ICON;
  }

  function isOnSnake(px, py) {
    for (let i = 0; i < snakeLength; i++) {
      if (x[i] === px && y[i] === py) return true;
    }
    return false;
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
      placeFood(true);
    }
  }

  function increaseSpeed() {
    DELAY--;
    level++;
    if (gameTimer) clearInterval(gameTimer);
    gameTimer = setInterval(gameTick, DELAY);
    console.log("Speed increased.");
  }

  function lockWalls() {
    wallsBlocked = true;
    console.log("Walls locked.");
    updateEditMenuChecks();
    paint();
  }

  function unlockWalls() {
    wallsBlocked = false;
    console.log("Walls unlocked.");
    updateEditMenuChecks();
    paint();
  }

  function updateEditMenuChecks() {
    document.getElementById("lockWallsOption").textContent =
      (wallsBlocked ? "✓ " : "  ") + "Lock Walls";
    document.getElementById("unlockWallsOption").textContent =
      (!wallsBlocked ? "✓ " : "  ") + "Unlock Walls";
    document.getElementById("orangeSnakeOption").textContent =
      (skin === SKINS.male ? "✓ " : "  ") + "Orange Snake";
    document.getElementById("greenSnakeOption").textContent =
      (skin === SKINS.female ? "✓ " : "  ") + "Green Snake";
  }

  function currentDirectionName() {
    if (left) return "left";
    if (right) return "right";
    if (up) return "up";
    if (down) return "down";
    return null;
  }

  function requestDirection(name) {
    if (queuedDirection) return; // one pending change is enough until the next tick applies it
    const base = currentDirectionName();
    if (base && OPPOSITES[name] === base) return;
    if (name === base) return;
    queuedDirection = name;
  }

  function applyQueuedDirection() {
    if (!queuedDirection) return;
    left = queuedDirection === "left";
    right = queuedDirection === "right";
    up = queuedDirection === "up";
    down = queuedDirection === "down";
    queuedDirection = null;
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
      if (y[0] > HEIGHT - CELL_SIZE) gameRunning = false;
      if (y[0] < 0) gameRunning = false;
      if (x[0] > WIDTH - CELL_SIZE) gameRunning = false;
      if (x[0] < 0) gameRunning = false;
    } else {
      if (y[0] > HEIGHT - CELL_SIZE) y[0] = 0;
      if (y[0] < 0) y[0] = HEIGHT - CELL_SIZE;
      if (x[0] > WIDTH - CELL_SIZE) x[0] = 0;
      if (x[0] < 0) x[0] = WIDTH - CELL_SIZE;
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

  function togglePause() {
    if (gamePaused) {
      resume();
    } else {
      pause();
    }
  }

  function gameTick() {
    if (gameRunning) {
      applyQueuedDirection();
      move();
      checkCollision();
      checkFood();
    }
    paint();
  }

  function paint() {
    updateScoreDisplay();
    ctx.clearRect(0, 0, WIDTH, HEIGHT);

    if (gameRunning) {
      ctx.fillStyle = BG_COLOR;
      ctx.fillRect(0, 0, WIDTH, HEIGHT);
      drawFood();

      for (let i = 0; i < snakeLength; i++) {
        ctx.fillStyle = i === 0 ? skin.head : skin.body;
        ctx.fillRect(x[i], y[i], CELL_SIZE, CELL_SIZE);
      }

      drawWallsIndicator();
      drawPauseOverlay();
    } else {
      drawGameOver();
    }
  }

  function updateScoreDisplay() {
    document.getElementById("status").textContent =
      "Score: " + SCORE + "  |  Speed: " + DELAY + "ms" + "  |  Level: " + level;
  }

  function drawFood() {
    ctx.fillStyle = food.color;
    ctx.font = FOOD_FONT;
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillText(food.char, foodX + CELL_SIZE / 2, foodY + CELL_SIZE / 2 + 1);
    ctx.textAlign = "left";
    ctx.textBaseline = "alphabetic";
  }

  function drawWallsIndicator() {
    if (!wallsBlocked) return;
    ctx.strokeStyle = "#ffb454";
    ctx.lineWidth = 3;
    ctx.strokeRect(1.5, 1.5, WIDTH - 3, HEIGHT - 3);
  }

  function drawPauseOverlay() {
    if (!gamePaused) return;

    ctx.fillStyle = "white";
    ctx.textAlign = "center";

    ctx.font = "bold 40px 'JetBrains Mono', Consolas, monospace";
    ctx.fillText("PAUSED", WIDTH / 2, HEIGHT / 2);

    ctx.font = "bold 15px 'JetBrains Mono', Consolas, monospace";
    ctx.fillText("P or SPACE to resume :)", WIDTH / 2, HEIGHT / 2 + 25);

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

  window.addEventListener("keydown", (e) => {
    if (gamePaused && ["ArrowLeft", "ArrowRight", "ArrowUp", "ArrowDown"].includes(e.code)) {
      resume();
    }
    switch (e.code) {
      case "ArrowLeft":
        requestDirection("left");
        e.preventDefault();
        break;
      case "ArrowRight":
        requestDirection("right");
        e.preventDefault();
        break;
      case "ArrowUp":
        requestDirection("up");
        e.preventDefault();
        break;
      case "ArrowDown":
        requestDirection("down");
        e.preventDefault();
        break;
      case "Escape":
        exitGame();
        break;
      case "KeyP":
      case "Space":
        e.preventDefault();
        togglePause();
        break;
      case "KeyM":
        if (e.ctrlKey || e.metaKey) { e.preventDefault(); chooseSnakeSkin(0); }
        break;
      case "KeyF":
        if (e.ctrlKey || e.metaKey) { e.preventDefault(); chooseSnakeSkin(1); }
        break;
    }
  });

  window.addEventListener("blur", () => {
    if (gameRunning && !gamePaused) pause();
  });

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

  function manual() {
    showMessage(
      "How to Play",
      "Controls:\n\n" +
      "* Arrow keys - UP, DOWN, LEFT and RIGHT.\n\n" +
      "How to Play:\n\n" +
      "* Try to eat as many fruits as possible and rack up points,\n" +
      "while your little snake keeps growing with every bite.\n\n" +
      "* Use the arrow keys to move the snake.\n\n" +
      "* Pause and resume the game at any time with P or SPACE\n\n" +
      "Good luck, little snake!"
    );
  }

  function aboutDeveloper() {
    showMessage(
      "About the Developer:",
      "Bruno Vieira\n\n" +
      "www.brunovidasi.com | contact@brunovidasi.com\n\n" +
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

  const actions = {
    newGame: startNewGame,
    newEasy: () => newGameWithDifficulty(150),
    newMedium: () => newGameWithDifficulty(100),
    newHard: () => newGameWithDifficulty(60),
    pause: pause,
    resume: resume,
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

  function start() {
    chooseSnakeSkin(0);
    startGame();
    paint();
  }

  if (document.fonts && document.fonts.load) {
    document.fonts.load(FOOD_FONT).catch(() => {}).then(start);
  } else {
    start();
  }
})();
