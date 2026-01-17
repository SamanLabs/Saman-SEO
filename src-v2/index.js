import { render } from '@wordpress/element';
import App from './App';

import './index.css';

const initialView = window?.SamanSEOSettings?.initialView || 'dashboard';

render(<App initialView={initialView} />, document.getElementById('saman-seo-v2-root'));
