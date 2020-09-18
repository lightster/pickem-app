const http = require('http');
const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch({
    args: ["--no-sandbox", "--disable-setuid-sandbox"],
  });

  const server = http.createServer(async (req, res) => {
    const url = new URL(req.url, `http://${req.headers.host}`);

    if (url.pathname.replace(/\/+$/, '') !== '/scores') {
      console.log(`${req.method} ${url.pathname}${url.search} 404`);
      res.writeHead(404, {
        'Content-Type': 'application/javascript',
      });
      res.end(JSON.stringify({error: `404 page not found`}));
      return;
    }

    const page = await browser.newPage();
    try {
      console.log(`${req.method} ${url.pathname}${url.search} -`);

      let scoreUrls = [];
      page.on('requestfinished', async request => {
        const url = request.url();
        if (!url.startsWith('https://api.nfl.com/v3/shield/?query=query')
          || !url.includes('gameDetailsByIds')
        ) {
          return;
        }

        scoreUrls.push(url);
      });
      await page.goto(`https://www.nfl.com`, {waitUntil: 'networkidle2'});
      page.removeAllListeners();

      let scoreUrl;
      const games = {};
      while (scoreUrl = scoreUrls.pop()) {
        let scorePage;
        try {
          scorePage = await browser.newPage();
          const response = await scorePage.goto(scoreUrl);
          const json = await response.json();

          if (json.data && json.data.viewer && json.data.viewer.gameDetailsByIds) {
            json.data.viewer.gameDetailsByIds.forEach(game => {
              games[game.id] = game;
            });
          }
        } finally {
          if (scorePage) {
            scorePage.close();
          }
        }
      }

      res.writeHead(200, {'Content-Type': 'application/json'});
      res.write(JSON.stringify(games, null, 2));
      res.end();

      console.log(`${req.method} ${url.pathname}${url.search} 200`);
    } catch (e) {
      console.log(`${req.method} ${url.pathname}${url.search} 500`);
      console.error(e);
      res.writeHead(500);
      res.end();
    } finally {
      await page.close();
    }
  });
  console.log('Starting server');
  server.listen(80);
})();
