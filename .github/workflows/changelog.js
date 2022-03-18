// Replaces the `<!-- changelog -->` comment in the README.md with the release notes from GitHub.
// Usage locally:
//   TOKEN=... GITHUB_REPOSITORY='Automattic/ad-code-manager' node .github/workflows/changelog.js
// Where TOKEN is a personal GitHub token that has access to the repo: https://github.com/settings/tokens
// In a GitHub Workflow, TOKEN can be passed the special ${{ secrets.GITHUB_TOKEN }} token.

const github = require('@actions/github');
const semver = require('semver');
const replace = require('replace-in-file');

const filename = process.argv[2] || 'README.md';
const myToken = process.env.TOKEN;

async function run() {
	const api = new github.GitHub(myToken);

	const { data: releases } = await api.repos.listReleases( github.context.repo );

	let published = releases.filter( release =>
		! release.draft && ! release.prerelease
	);

	let sorted = published.sort( ( a, b ) =>
		semver.rcompare( semver.coerce( a.tag_name ), semver.coerce( b.tag_name ) )
	);

	let changelog = sorted.reduce( ( changelog, release ) =>
			`${changelog}

### ${release.tag_name}

${release.body}`
		, '## Changelog' );

	try {
		const results = await replace( {
			files: filename,
			from: '<!-- changelog -->',
			to: changelog,
		} );

		if ( results.filter( result => ! result.hasChanged ).length ) {
			console.error( 'No replacements made' );
			process.exitCode = 1;
		}
	} catch( exception ) {
		console.error( exception );
		process.exitCode = 1;
	}
}

run();
