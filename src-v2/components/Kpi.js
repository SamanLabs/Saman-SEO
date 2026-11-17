/**
 * KPI primitives, composed in the page that owns the numbers:
 *
 *   <KpiBand label={ __( 'Link summary', 'saman-seo' ) }>
 *     <MetricCard label="Total links" value="4,210" />
 *     <MetricCard label="Broken links" value="42" tone="danger" onClick={ … } />
 *     <MetricCard label="404 errors" value="38" spark={ <Sparkline data={ daily } /> } />
 *   </KpiBand>
 *
 * KpiBand is the one grid every row of tiles uses, so no page writes its own.
 * MetricCard is one tile. Both follow the ServerKit design system's
 * KpiBand / MetricCard / Sparkline.
 */

const TONE_COLORS = {
	success: 'var(--color-success)',
	warning: 'var(--color-warning)',
	danger: 'var(--color-danger)',
};

/**
 * Tiny trend line with a soft fill, sized by its container.
 *
 * Renders nothing unless there are at least two points and one of them is
 * non-zero, so a tile never shows a flat line that looks like data.
 *
 * @param {Object}   props
 * @param {number[]} props.data   Values, oldest first.
 * @param {string=}  props.tone   'success', 'warning' or 'danger'; default is the accent.
 * @param {number=}  props.height Height in px.
 * @param {string=}  props.label  Accessible description of the series.
 * @return {*} Sparkline or null.
 */
export const Sparkline = ( { data = [], tone, height = 36, label } ) => {
	const values = data.map( ( v ) => Number( v ) || 0 );
	if ( values.length < 2 || ! values.some( ( v ) => v !== 0 ) ) {
		return null;
	}
	const width = 100;
	const max = Math.max( ...values );
	const min = Math.min( ...values );
	const span = max - min || 1;
	const points = values.map( ( v, i ) => [
		( i / ( values.length - 1 ) ) * width,
		height - ( ( v - min ) / span ) * ( height - 6 ) - 3,
	] );
	const line = points
		.map( ( [ x, y ] ) => `${ x.toFixed( 2 ) },${ y.toFixed( 2 ) }` )
		.join( ' ' );
	const color = TONE_COLORS[ tone ] || 'var(--color-primary)';
	return (
		<svg
			className="kpi-spark"
			viewBox={ `0 0 ${ width } ${ height }` }
			height={ height }
			preserveAspectRatio="none"
			role={ label ? 'img' : undefined }
			aria-label={ label }
			aria-hidden={ label ? undefined : 'true' }
			style={ { color } }
		>
			<polygon
				className="kpi-spark__area"
				points={ `0,${ height } ${ line } ${ width },${ height }` }
			/>
			<polyline className="kpi-spark__line" points={ line } />
		</svg>
	);
};

/**
 * Responsive grid for a row of MetricCard tiles.
 *
 * A page renders the band in every state so its frame never changes:
 * `loading` shows shimmering placeholders while data is on the way, and
 * `idle` shows static dashes when there is nothing to count (for example the
 * feature is switched off).
 *
 * @param {Object}   props
 * @param {string=}  props.label     Accessible name for the group.
 * @param {string=}  props.className Extra class for the grid.
 * @param {boolean=} props.loading   Render `count` loading placeholders.
 * @param {boolean=} props.idle      Render `count` static placeholders.
 * @param {number=}  props.count     Placeholder tile count.
 * @param {*}        props.children  MetricCard tiles.
 * @return {*} KPI band.
 */
export const KpiBand = ( {
	label,
	className = '',
	loading = false,
	idle = false,
	count = 4,
	children,
} ) => {
	if ( loading || idle ) {
		return (
			<div
				className={ `kpi-band ${ className }`.trim() }
				aria-hidden="true"
			>
				{ Array.from( { length: count } ).map( ( _, i ) => (
					<div
						key={ i }
						className={ `kpi kpi--placeholder${
							loading ? ' is-loading' : ''
						}` }
					>
						<span className="kpi__value">{ '\u2013' }</span>
						<span className="kpi__label">{ '\u00a0' }</span>
					</div>
				) ) }
			</div>
		);
	}
	return (
		<div
			className={ `kpi-band ${ className }`.trim() }
			role="group"
			aria-label={ label }
		>
			{ children }
		</div>
	);
};

/**
 * One KPI tile: a value, its label, and optional detail.
 *
 * @param {Object}    props
 * @param {string}    props.label    What the number is.
 * @param {*}         props.value    The number (or short status word).
 * @param {string=}   props.unit     Small unit shown after the value.
 * @param {string=}   props.sub      One line of supporting detail.
 * @param {string=}   props.tone     Status dot: 'success', 'warning', 'danger' or 'off'.
 * @param {*=}        props.icon     Icon node shown beside the value.
 * @param {string=}   props.trend    Short delta shown top right, e.g. "+12%".
 * @param {string=}   props.trendDir 'up', 'down' or 'flat'.
 * @param {*=}        props.spark    Chart node (Sparkline) bled along the bottom edge.
 * @param {Function=} props.onClick  Makes the tile a button.
 * @param {*=}        props.children Extra content under the label.
 * @return {*} KPI tile.
 */
export const MetricCard = ( {
	label,
	value,
	unit,
	sub,
	tone,
	icon,
	trend,
	trendDir = 'flat',
	spark,
	onClick,
	children,
} ) => {
	const Tag = onClick ? 'button' : 'div';
	const tagProps = onClick ? { type: 'button', onClick } : {};
	return (
		<Tag
			className={ `kpi${ onClick ? ' kpi--clickable' : '' }${
				spark ? ' kpi--spark' : ''
			}` }
			{ ...tagProps }
		>
			<span className="kpi__head">
				{ icon && (
					<span className="kpi__icon" aria-hidden="true">
						{ icon }
					</span>
				) }
				<span className="kpi__body">
					<span className="kpi__value">
						{ value }
						{ unit && <small> { unit }</small> }
					</span>
					<span className="kpi__label">
						{ tone && (
							<span
								className={ `kpi__dot kpi__dot--${ tone }` }
								aria-hidden="true"
							/>
						) }
						{ label }
					</span>
				</span>
				{ trend && (
					<span className={ `kpi__trend kpi__trend--${ trendDir }` }>
						{ trend }
					</span>
				) }
			</span>
			{ sub && <span className="kpi__sub">{ sub }</span> }
			{ children }
			{ spark && <span className="kpi__spark">{ spark }</span> }
		</Tag>
	);
};

export default KpiBand;
