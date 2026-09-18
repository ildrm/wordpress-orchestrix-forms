export default [
  {
    files: ['assets/dist/*.js'],
    languageOptions: { ecmaVersion: 2022, sourceType: 'module' },
    rules: {
      'no-debugger': 'error',
      'no-console': 'error',
      'no-eval': 'error',
      'eqeqeq': 'error',
      'no-var': 'error'
    }
  }
];
