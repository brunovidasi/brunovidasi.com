#!/usr/bin/env node
// ==========================================================================
// Snapshots my public GitHub profile into json/github.json for the .gitconfig
// panel. Runs in the deploy workflow (on push and daily), so the token stays
// in CI and the browser only ever sees the generated JSON.
//
// GraphQL is used rather than REST because pinned repos and the contribution
// calendar only exist there — and GraphQL always needs a token.
//
// Usage: GH_TOKEN=<token> node scripts/fetch-github.mjs
//   Locally, `GH_TOKEN=$(gh auth token) node scripts/fetch-github.mjs` works.
// ==========================================================================

import { writeFile } from 'node:fs/promises';

const LOGIN = 'brunovidasi';
const OUT_FILE = new URL('../json/github.json', import.meta.url);
const TOP_LANGUAGE_COUNT = 6;

const token = process.env.GH_TOKEN || process.env.GITHUB_TOKEN;
if(!token){
  console.error('GH_TOKEN is not set.');
  process.exit(1);
}

const REPO_FIELDS = `
  name
  description
  url
  homepageUrl
  stargazerCount
  forkCount
  pushedAt
  primaryLanguage { name color }
`;

const QUERY = `
query($login: String!) {
  user(login: $login) {
    login
    name
    avatarUrl(size: 200)
    bio
    location
    company
    url
    createdAt
    followers { totalCount }
    following { totalCount }
    pinnedItems(first: 6, types: REPOSITORY) {
      nodes { ... on Repository { ${REPO_FIELDS} } }
    }
    repositories(first: 100, privacy: PUBLIC, ownerAffiliations: OWNER, isFork: false,
                 orderBy: { field: PUSHED_AT, direction: DESC }) {
      totalCount
      nodes {
        ${REPO_FIELDS}
        languages(first: 10, orderBy: { field: SIZE, direction: DESC }) {
          edges { size node { name color } }
        }
      }
    }
    contributionsCollection {
      contributionCalendar {
        totalContributions
        weeks { contributionDays { date contributionCount } }
      }
    }
  }
}`;

async function query(){
  const res = await fetch('https://api.github.com/graphql', {
    method: 'POST',
    headers: {
      Authorization: `bearer ${token}`,
      'Content-Type': 'application/json',
      'User-Agent': 'brunovidasi.com'
    },
    body: JSON.stringify({ query: QUERY, variables: { login: LOGIN } })
  });
  if(!res.ok) throw new Error(`GitHub API ${res.status}: ${await res.text()}`);
  const body = await res.json();
  if(body.errors) throw new Error(body.errors.map(e => e.message).join('; '));
  return body.data.user;
}

/** Drops the per-repo language breakdown, which only feeds the totals. */
function slimRepo({ languages, ...repo }){
  return {
    ...repo,
    language: repo.primaryLanguage ? repo.primaryLanguage.name : null,
    languageColor: repo.primaryLanguage ? repo.primaryLanguage.color : null,
    primaryLanguage: undefined
  };
}

/** Bytes of code per language across every public repo, as percentages. */
function topLanguages(repos){
  const totals = new Map();
  repos.forEach(repo => repo.languages.edges.forEach(({ size, node }) => {
    const entry = totals.get(node.name) || { name: node.name, color: node.color, size: 0 };
    entry.size += size;
    totals.set(node.name, entry);
  }));
  const all = [...totals.values()].sort((a, b) => b.size - a.size);
  const sum = all.reduce((n, l) => n + l.size, 0) || 1;
  return all.slice(0, TOP_LANGUAGE_COUNT).map(l => ({
    name: l.name,
    color: l.color,
    percent: Math.round(l.size / sum * 1000) / 10
  }));
}

async function main(){
  const user = await query();
  const repos = user.repositories.nodes;
  const calendar = user.contributionsCollection.contributionCalendar;

  const data = {
    generatedAt: new Date().toISOString(),
    profile: {
      login: user.login,
      name: user.name,
      avatarUrl: user.avatarUrl,
      bio: user.bio,
      location: user.location,
      company: user.company,
      url: user.url,
      createdAt: user.createdAt,
      followers: user.followers.totalCount,
      following: user.following.totalCount,
      publicRepos: user.repositories.totalCount,
      totalStars: repos.reduce((n, r) => n + r.stargazerCount, 0)
    },
    pinned: user.pinnedItems.nodes.map(slimRepo),
    // Every public repo, most recently pushed first.
    repos: repos.map(slimRepo),
    languages: topLanguages(repos),
    contributions: {
      total: calendar.totalContributions,
      days: calendar.weeks.flatMap(w => w.contributionDays.map(d => [d.date, d.contributionCount]))
    }
  };

  await writeFile(OUT_FILE, JSON.stringify(data) + '\n');
  console.log(`Wrote json/github.json: ${data.pinned.length} pinned, ${data.profile.publicRepos} repos, ${data.contributions.total} contributions.`);
}

main().catch(err => {
  console.error(err.message);
  process.exit(1);
});
