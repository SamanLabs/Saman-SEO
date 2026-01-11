import { render } from '@wordpress/element';
import App from './App';

import './index.css';

const initialView = window?.wpseopilotV2Settings?.initialView || 'dashboard';

render(<App initialView={initialView} />, document.getElementById('wpseopilot-v2-root'));
