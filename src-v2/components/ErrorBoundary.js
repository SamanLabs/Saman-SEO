import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

class ErrorBoundary extends Component {
	constructor( props ) {
		super( props );
		this.state = { hasError: false, error: null };
	}

	static getDerivedStateFromError( error ) {
		return { hasError: true, error };
	}

	componentDidCatch( error, errorInfo ) {
		// eslint-disable-next-line no-console
		console.error( 'Saman SEO admin error:', error, errorInfo );
	}

	render() {
		if ( ! this.state.hasError ) {
			return this.props.children;
		}

		return (
			<div className="saman-seo-admin-error">
				<h2>{ __( 'Something went wrong', 'saman-seo' ) }</h2>
				<p>
					{ __(
						'Saman SEO encountered an unexpected error. Try refreshing the page.',
						'saman-seo'
					) }
				</p>
				{ this.state.error && this.state.error.message && (
					<details>
						<summary>
							{ __( 'Error details', 'saman-seo' ) }
						</summary>
						<pre>{ this.state.error.message }</pre>
					</details>
				) }
			</div>
		);
	}
}

export default ErrorBoundary;
