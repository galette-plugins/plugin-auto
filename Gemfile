# Local preview only; GitHub Pages needs none of this.
#
# Deliberately NOT the `github-pages` gem: it pins liquid 4.0.3, which calls
# String#tainted?, removed in Ruby 3.2 — so it cannot build on a current Ruby.
# Jekyll 4 with the two plugins GitHub Pages itself enables gets the same result
# here, and the theme's SCSS stays on `@import` so it compiles under both.
source "https://rubygems.org"

gem "jekyll", "~> 4.4"

group :jekyll_plugins do
  gem "jekyll-remote-theme"
  gem "jekyll-github-metadata"
end
