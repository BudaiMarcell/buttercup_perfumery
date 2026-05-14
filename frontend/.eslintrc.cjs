
module.exports = {
  root: true,
  env: {
    browser: true,
    es2022: true,
    node: true,
  },
  extends: [
    'eslint:recommended',
  ],
  parserOptions: {
    ecmaVersion: 'latest',
    sourceType: 'module',
  },
  rules: {
    'no-console': ['error', { allow: ['warn', 'error'] }],
    'no-debugger': 'error',
    'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
  },
  ignorePatterns: [
    'dist/',
    'node_modules/',
    'public/config.js',
  ],
}
