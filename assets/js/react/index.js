import React from 'react';
import ReactDOM from 'react-dom';

const App = () => {
  return <h1>Hello World, from React!</h1>;
};

document.addEventListener('DOMContentLoaded', () => {
  const rootElement = document.getElementById('wp-seo-pilot-react-app');
  if (rootElement) {
    ReactDOM.render(<App />, rootElement);
  }
});
