# Releasing

Releases are deliberately tag-driven. A maintainer creates a concrete `v0.x.y` tag only after the `master` branch CI is green.

## Publish a release

1. Confirm the working tree is clean and the target commit is on `master`.
2. Review the self-hosting upgrade impact, especially migrations and configuration changes.
3. Create and push an annotated release tag:

   ```bash
   git tag -a v0.1.0 -m "VersionTracker v0.1.0"
   git push origin v0.1.0
   ```

4. Verify the Release workflow. It repeats application validation, builds and publishes the container with provenance and an SBOM, smoke-tests the published image by digest, and creates the GitHub Release.
5. Check the generated release notes. Add a concise **Upgrade notes** section that calls out migrations, changed environment variables, deprecations, and any manual operator action.

## Published images

Each release produces:

- `ghcr.io/soerennb/version-tracker:v0.x.y` for a precise deployment.
- `ghcr.io/soerennb/version-tracker:v0.x` for the current patch in a minor line.
- `ghcr.io/soerennb/version-tracker:latest` for evaluation only.

Operators should deploy the precise tag or the image digest included in the GitHub Release.
