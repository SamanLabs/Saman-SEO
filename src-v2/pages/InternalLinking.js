import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import SubTabs from '../components/SubTabs';
import { KpiBand, MetricCard } from '../components/Kpi';
import useUrlTab from '../hooks/useUrlTab';
import { __ } from '@wordpress/i18n';
import { confirmDialog } from '../components/Dialogs';
const linkingTabs = [
	{
		id: 'rules',
		label: __( 'Rules', 'saman-seo' ),
	},
	{
		id: 'categories',
		label: __( 'Categories', 'saman-seo' ),
	},
	{
		id: 'utm-templates',
		label: __( 'UTM templates', 'saman-seo' ),
	},
	{
		id: 'settings',
		label: __( 'Settings', 'saman-seo' ),
	},
];
const HEADING_LEVELS = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];
const InternalLinking = () => {
	const [ activeTab, setActiveTab ] = useUrlTab( {
		tabs: linkingTabs,
		defaultTab: 'rules',
	} );

	// Rules state
	const [ rules, setRules ] = useState( [] );
	const [ rulesLoading, setRulesLoading ] = useState( true );
	const [ categories, setCategories ] = useState( [] );
	const [ templates, setTemplates ] = useState( [] );
	const [ settings, setSettings ] = useState( {} );
	const [ , setStats ] = useState( {
		total_rules: 0,
		active_rules: 0,
		categories: 0,
		utm_templates: 0,
	} );

	// Filters
	const [ filterStatus, setFilterStatus ] = useState( '' );
	const [ filterCategory, setFilterCategory ] = useState( '' );
	const [ filterSearch, setFilterSearch ] = useState( '' );

	// Bulk selection
	const [ selectedRules, setSelectedRules ] = useState( [] );
	const [ bulkAction, setBulkAction ] = useState( '' );
	const [ bulkCategory, setBulkCategory ] = useState( '' );

	// Modal states
	const [ ruleModalOpen, setRuleModalOpen ] = useState( false );
	const [ editingRule, setEditingRule ] = useState( null );
	const [ categoryModalOpen, setCategoryModalOpen ] = useState( false );
	const [ editingCategory, setEditingCategory ] = useState( null );
	const [ templateModalOpen, setTemplateModalOpen ] = useState( false );
	const [ editingTemplate, setEditingTemplate ] = useState( null );

	// Fetch all data
	const fetchData = useCallback( async () => {
		setRulesLoading( true );
		try {
			const [
				rulesRes,
				categoriesRes,
				templatesRes,
				settingsRes,
				statsRes,
			] = await Promise.all( [
				apiFetch( {
					path: '/saman-seo/v1/internal-links/rules',
				} ),
				apiFetch( {
					path: '/saman-seo/v1/internal-links/categories',
				} ),
				apiFetch( {
					path: '/saman-seo/v1/internal-links/templates',
				} ),
				apiFetch( {
					path: '/saman-seo/v1/internal-links/settings',
				} ),
				apiFetch( {
					path: '/saman-seo/v1/internal-links/stats',
				} ),
			] );
			if ( rulesRes.success ) setRules( rulesRes.data );
			if ( categoriesRes.success ) setCategories( categoriesRes.data );
			if ( templatesRes.success ) setTemplates( templatesRes.data );
			if ( settingsRes.success ) setSettings( settingsRes.data );
			if ( statsRes.success ) setStats( statsRes.data );
		} catch ( error ) {
			console.error( 'Failed to fetch internal linking data:', error );
		} finally {
			setRulesLoading( false );
		}
	}, [] );
	useEffect( () => {
		fetchData();
	}, [ fetchData ] );

	// Filter rules
	const filteredRules = rules.filter( ( rule ) => {
		if ( filterStatus && rule.status !== filterStatus ) return false;
		if ( filterCategory && rule.category !== filterCategory ) return false;
		if ( filterSearch ) {
			const search = filterSearch.toLowerCase();
			const inTitle = ( rule.title || '' )
				.toLowerCase()
				.includes( search );
			const inKeywords = ( rule.keywords || [] ).some( ( k ) =>
				k.toLowerCase().includes( search )
			);
			if ( ! inTitle && ! inKeywords ) return false;
		}
		return true;
	} );

	// Get category by ID
	const getCategoryById = ( id ) => categories.find( ( c ) => c.id === id );

	// Rule actions
	const handleDeleteRule = async ( id ) => {
		if (
			! await confirmDialog(
				__( 'Are you sure you want to delete this rule?', 'saman-seo' ),
				{ confirmLabel: __( 'Delete', 'saman-seo' ), danger: true }
			)
		)
			return;
		try {
			await apiFetch( {
				path: `/saman-seo/v1/internal-links/rules/${ id }`,
				method: 'DELETE',
			} );
			setRules( rules.filter( ( r ) => r.id !== id ) );
			setSelectedRules( selectedRules.filter( ( rid ) => rid !== id ) );
		} catch ( error ) {
			console.error( 'Failed to delete rule:', error );
		}
	};
	const handleToggleRule = async ( id ) => {
		try {
			const res = await apiFetch( {
				path: `/saman-seo/v1/internal-links/rules/${ id }/toggle`,
				method: 'POST',
			} );
			if ( res.success ) {
				setRules(
					rules.map( ( r ) => ( r.id === id ? res.data : r ) )
				);
			}
		} catch ( error ) {
			console.error( 'Failed to toggle rule:', error );
		}
	};
	const handleDuplicateRule = async ( id ) => {
		try {
			const res = await apiFetch( {
				path: `/saman-seo/v1/internal-links/rules/${ id }/duplicate`,
				method: 'POST',
			} );
			if ( res.success ) {
				setRules( [ res.data, ...rules ] );
			}
		} catch ( error ) {
			console.error( 'Failed to duplicate rule:', error );
		}
	};
	const handleBulkAction = async () => {
		if ( ! bulkAction || selectedRules.length === 0 ) return;
		try {
			const payload = {
				ids: selectedRules,
				action: bulkAction,
			};
			if ( bulkAction === 'change_category' ) {
				payload.category = bulkCategory;
			}
			await apiFetch( {
				path: '/saman-seo/v1/internal-links/rules/bulk',
				method: 'POST',
				data: payload,
			} );
			setSelectedRules( [] );
			setBulkAction( '' );
			setBulkCategory( '' );
			fetchData();
		} catch ( error ) {
			console.error( 'Failed to perform bulk action:', error );
		}
	};
	const handleSelectAll = ( e ) => {
		if ( e.target.checked ) {
			setSelectedRules( filteredRules.map( ( r ) => r.id ) );
		} else {
			setSelectedRules( [] );
		}
	};
	const handleSelectRule = ( id ) => {
		if ( selectedRules.includes( id ) ) {
			setSelectedRules( selectedRules.filter( ( rid ) => rid !== id ) );
		} else {
			setSelectedRules( [ ...selectedRules, id ] );
		}
	};

	// Category actions
	const handleDeleteCategory = async ( id ) => {
		const category = getCategoryById( id );
		if ( ! category ) return;
		const rulesInCategory = rules.filter(
			( r ) => r.category === id
		).length;
		let reassign = null;
		if ( rulesInCategory > 0 ) {
			const message = `This category has ${ rulesInCategory } rule(s). Delete anyway and remove category from rules?`;
			if ( ! await confirmDialog( message, { confirmLabel: __( 'Delete', 'saman-seo' ), danger: true } ) ) return;
			reassign = '__none__';
		} else {
			if (
				! await confirmDialog(
					__(
						'Are you sure you want to delete this category?',
						'saman-seo'
					),
					{ confirmLabel: __( 'Delete', 'saman-seo' ), danger: true }
				)
			)
				return;
		}
		try {
			await apiFetch( {
				path: `/saman-seo/v1/internal-links/categories/${ id }`,
				method: 'DELETE',
				data: reassign
					? {
							reassign,
					  }
					: undefined,
			} );
			setCategories( categories.filter( ( c ) => c.id !== id ) );
			if ( reassign ) {
				setRules(
					rules.map( ( r ) =>
						r.category === id
							? {
									...r,
									category: '',
							  }
							: r
					)
				);
			}
		} catch ( error ) {
			console.error( 'Failed to delete category:', error );
		}
	};

	// Template actions
	const handleDeleteTemplate = async ( id ) => {
		if (
			! await confirmDialog(
				__(
					'Are you sure you want to delete this UTM template?',
					'saman-seo'
				),
				{ confirmLabel: __( 'Delete', 'saman-seo' ), danger: true }
			)
		)
			return;
		try {
			await apiFetch( {
				path: `/saman-seo/v1/internal-links/templates/${ id }`,
				method: 'DELETE',
			} );
			setTemplates( templates.filter( ( t ) => t.id !== id ) );
		} catch ( error ) {
			console.error( 'Failed to delete template:', error );
		}
	};

	// Save settings
	const handleSaveSettings = async () => {
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/internal-links/settings',
				method: 'POST',
				data: settings,
			} );
			if ( res.success ) {
				setSettings( res.data );
			}
		} catch ( error ) {
			console.error( 'Failed to save settings:', error );
		}
	};
	const openAddRule = () => {
		setEditingRule( null );
		setRuleModalOpen( true );
	};
	const openAddCategory = () => {
		setEditingCategory( null );
		setCategoryModalOpen( true );
	};
	const openAddTemplate = () => {
		setEditingTemplate( null );
		setTemplateModalOpen( true );
	};

	// The band counts the lists the tabs show, so it follows every add,
	// toggle and delete without another request.
	const activeRuleCount = rules.filter(
		( rule ) => rule.status === 'active'
	).length;
	return (
		<div className="page">
			{ rulesLoading ? (
				<KpiBand loading count={ 4 } />
			) : (
				<KpiBand
					label={ __( 'Internal linking summary', 'saman-seo' ) }
				>
					<MetricCard
						label={ __( 'Total rules', 'saman-seo' ) }
						value={ rules.length.toLocaleString() }
					/>
					<MetricCard
						label={ __( 'Active rules', 'saman-seo' ) }
						value={ activeRuleCount.toLocaleString() }
						tone="success"
					/>
					<MetricCard
						label={ __( 'Categories', 'saman-seo' ) }
						value={ categories.length.toLocaleString() }
					/>
					<MetricCard
						label={ __( 'UTM templates', 'saman-seo' ) }
						value={ templates.length.toLocaleString() }
					/>
				</KpiBand>
			) }
			<div className="page-header">
				<div>
					<h1>{ __( 'Internal linking', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Automatically add internal links to your content based on keywords.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="header-actions">
					{ activeTab === 'rules' && (
						<button
							type="button"
							className="button primary"
							onClick={ openAddRule }
						>
							{ __( 'Add rule', 'saman-seo' ) }
						</button>
					) }
					{ activeTab === 'categories' && (
						<button
							type="button"
							className="button primary"
							onClick={ openAddCategory }
						>
							{ __( 'Add category', 'saman-seo' ) }
						</button>
					) }
					{ activeTab === 'utm-templates' && (
						<button
							type="button"
							className="button primary"
							onClick={ openAddTemplate }
						>
							{ __( 'Add template', 'saman-seo' ) }
						</button>
					) }
					{ activeTab === 'settings' && (
						<button
							type="button"
							className="button primary"
							onClick={ handleSaveSettings }
						>
							{ __( 'Save settings', 'saman-seo' ) }
						</button>
					) }
				</div>
			</div>

			<SubTabs
				tabs={ linkingTabs }
				activeTab={ activeTab }
				onChange={ setActiveTab }
				ariaLabel={ __( 'Internal linking sections', 'saman-seo' ) }
			/>

			{ activeTab === 'rules' && (
				<RulesTab
					rules={ filteredRules }
					rulesLoading={ rulesLoading }
					categories={ categories }
					selectedRules={ selectedRules }
					filterStatus={ filterStatus }
					filterCategory={ filterCategory }
					filterSearch={ filterSearch }
					bulkAction={ bulkAction }
					bulkCategory={ bulkCategory }
					onFilterStatus={ setFilterStatus }
					onFilterCategory={ setFilterCategory }
					onFilterSearch={ setFilterSearch }
					onSelectAll={ handleSelectAll }
					onSelectRule={ handleSelectRule }
					onEditRule={ ( rule ) => {
						setEditingRule( rule );
						setRuleModalOpen( true );
					} }
					onDeleteRule={ handleDeleteRule }
					onToggleRule={ handleToggleRule }
					onDuplicateRule={ handleDuplicateRule }
					onBulkAction={ setBulkAction }
					onBulkCategory={ setBulkCategory }
					onApplyBulk={ handleBulkAction }
					onAddRule={ openAddRule }
					getCategoryById={ getCategoryById }
				/>
			) }

			{ activeTab === 'categories' && (
				<CategoriesTab
					categories={ categories }
					templates={ templates }
					loading={ rulesLoading }
					onEdit={ ( cat ) => {
						setEditingCategory( cat );
						setCategoryModalOpen( true );
					} }
					onDelete={ handleDeleteCategory }
					onAdd={ openAddCategory }
				/>
			) }

			{ activeTab === 'utm-templates' && (
				<TemplatesTab
					templates={ templates }
					loading={ rulesLoading }
					onEdit={ ( tpl ) => {
						setEditingTemplate( tpl );
						setTemplateModalOpen( true );
					} }
					onDelete={ handleDeleteTemplate }
					onAdd={ openAddTemplate }
				/>
			) }

			{ activeTab === 'settings' && (
				<SettingsTab settings={ settings } onChange={ setSettings } />
			) }

			{ ruleModalOpen && (
				<RuleModal
					rule={ editingRule }
					categories={ categories }
					templates={ templates }
					onClose={ () => {
						setRuleModalOpen( false );
						setEditingRule( null );
					} }
					onSave={ ( saved ) => {
						if ( editingRule ) {
							setRules(
								rules.map( ( r ) =>
									r.id === saved.id ? saved : r
								)
							);
						} else {
							setRules( [ saved, ...rules ] );
						}
						setRuleModalOpen( false );
						setEditingRule( null );
					} }
				/>
			) }

			{ categoryModalOpen && (
				<CategoryModal
					category={ editingCategory }
					templates={ templates }
					onClose={ () => {
						setCategoryModalOpen( false );
						setEditingCategory( null );
					} }
					onSave={ ( saved ) => {
						if ( editingCategory ) {
							setCategories(
								categories.map( ( c ) =>
									c.id === saved.id ? saved : c
								)
							);
						} else {
							setCategories( [ ...categories, saved ] );
						}
						setCategoryModalOpen( false );
						setEditingCategory( null );
					} }
				/>
			) }

			{ templateModalOpen && (
				<TemplateModal
					template={ editingTemplate }
					onClose={ () => {
						setTemplateModalOpen( false );
						setEditingTemplate( null );
					} }
					onSave={ ( saved ) => {
						if ( editingTemplate ) {
							setTemplates(
								templates.map( ( t ) =>
									t.id === saved.id ? saved : t
								)
							);
						} else {
							setTemplates( [ ...templates, saved ] );
						}
						setTemplateModalOpen( false );
						setEditingTemplate( null );
					} }
				/>
			) }
		</div>
	);
};

// Rules Tab Component
const RulesTab = ( {
	rules,
	rulesLoading,
	categories,
	selectedRules,
	filterStatus,
	filterCategory,
	filterSearch,
	bulkAction,
	bulkCategory,
	onFilterStatus,
	onFilterCategory,
	onFilterSearch,
	onSelectAll,
	onSelectRule,
	onEditRule,
	onDeleteRule,
	onToggleRule,
	onDuplicateRule,
	onBulkAction,
	onBulkCategory,
	onApplyBulk,
	onAddRule,
	getCategoryById,
} ) => {
	const clearFilters = () => {
		onFilterStatus( '' );
		onFilterCategory( '' );
		onFilterSearch( '' );
	};
	const hasFilters = filterStatus || filterCategory || filterSearch;
	let content;
	if ( rulesLoading ) {
		content = (
			<div className="loading-state">
				{ __( 'Loading rules…', 'saman-seo' ) }
			</div>
		);
	} else if ( rules.length === 0 ) {
		content = (
			<div className="empty-state">
				<div className="empty-state__icon">
					<svg
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						strokeWidth="1.5"
						width="48"
						height="48"
						aria-hidden="true"
					>
						<path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71" />
						<path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71" />
					</svg>
				</div>
				<h3>
					{ hasFilters
						? __( 'No rules match your filters', 'saman-seo' )
						: __( 'No linking rules yet', 'saman-seo' ) }
				</h3>
				<p>
					{ hasFilters
						? __( 'Try adjusting your filters.', 'saman-seo' )
						: __(
								'Create your first internal link rule to get started.',
								'saman-seo'
						  ) }
				</p>
				{ ! hasFilters && (
					<button
						type="button"
						className="button primary"
						onClick={ onAddRule }
					>
						{ __( 'Add rule', 'saman-seo' ) }
					</button>
				) }
			</div>
		);
	} else {
		content = (
			<div className="table-wrap">
				<table className="data-table">
					<thead>
						<tr>
							<th className="checkbox-col">
								<input
									type="checkbox"
									aria-label={ __(
										'Select all rules',
										'saman-seo'
									) }
									checked={
										selectedRules.length === rules.length &&
										rules.length > 0
									}
									onChange={ onSelectAll }
								/>
							</th>
							<th className="col-title">
								{ __( 'Rule', 'saman-seo' ) }
							</th>
							<th>{ __( 'Destination', 'saman-seo' ) }</th>
							<th className="col-status">
								{ __( 'Status', 'saman-seo' ) }
							</th>
							<th className="col-actions">
								{ __( 'Actions', 'saman-seo' ) }
							</th>
						</tr>
					</thead>
					<tbody>
						{ rules.map( ( rule ) => {
							const category = getCategoryById( rule.category );
							return (
								<tr key={ rule.id }>
									<td className="checkbox-col">
										<input
											type="checkbox"
											aria-label={ rule.title }
											checked={ selectedRules.includes(
												rule.id
											) }
											onChange={ () =>
												onSelectRule( rule.id )
											}
										/>
									</td>
									<td className="rule-cell">
										<button
											type="button"
											className="link-button"
											onClick={ () => onEditRule( rule ) }
										>
											{ rule.title }
										</button>
										<div className="rule-cell__meta">
											{ category && (
												<span
													className="category-pill"
													style={ {
														'--pill-color':
															category.color,
													} }
												>
													{ category.name }
												</span>
											) }
											<span className="keywords-preview">
												{ ( rule.keywords || [] )
													.slice( 0, 3 )
													.join( ', ' ) }
												{ ( rule.keywords || [] )
													.length > 3 && (
													<span className="muted">
														{ ' ' }
														+
														{ rule.keywords.length -
															3 }
													</span>
												) }
											</span>
										</div>
									</td>
									<td className="url-cell">
										{ rule.destination_url ? (
											<a
												href={ rule.destination_url }
												target="_blank"
												rel="noopener noreferrer"
												className="destination-link"
											>
												{ rule.destination_label }
											</a>
										) : (
											<span className="muted">—</span>
										) }
									</td>
									<td className="status-cell">
										<span
											className={ `pill${
												rule.status === 'active'
													? ' success'
													: ''
											}` }
										>
											{ rule.status === 'active'
												? __( 'Active', 'saman-seo' )
												: __(
														'Inactive',
														'saman-seo'
												  ) }
										</span>
									</td>
									<td className="action-cell">
										<div className="table-actions">
											<button
												type="button"
												className="table-actions__secondary"
												onClick={ () =>
													onEditRule( rule )
												}
											>
												{ __( 'Edit', 'saman-seo' ) }
											</button>
											<button
												type="button"
												className="table-actions__secondary"
												onClick={ () =>
													onDuplicateRule( rule.id )
												}
											>
												{ __(
													'Duplicate',
													'saman-seo'
												) }
											</button>
											<button
												type="button"
												className="table-actions__secondary"
												onClick={ () =>
													onToggleRule( rule.id )
												}
											>
												{ rule.status === 'active'
													? __(
															'Deactivate',
															'saman-seo'
													  )
													: __(
															'Activate',
															'saman-seo'
													  ) }
											</button>
											<button
												type="button"
												className="table-actions__danger"
												onClick={ () =>
													onDeleteRule( rule.id )
												}
											>
												{ __( 'Delete', 'saman-seo' ) }
											</button>
										</div>
									</td>
								</tr>
							);
						} ) }
					</tbody>
				</table>
			</div>
		);
	}
	return (
		<div className="page-body">
			<div className="page-toolbar">
				<div className="page-toolbar__filters">
					<input
						type="search"
						className="search-input"
						aria-label={ __( 'Search rules', 'saman-seo' ) }
						placeholder={ __( 'Search rules…', 'saman-seo' ) }
						value={ filterSearch }
						onChange={ ( e ) => onFilterSearch( e.target.value ) }
					/>
					<label className="filter-field">
						<span>{ __( 'Status', 'saman-seo' ) }</span>
						<select
							value={ filterStatus }
							onChange={ ( e ) =>
								onFilterStatus( e.target.value )
							}
						>
							<option value="">
								{ __( 'All statuses', 'saman-seo' ) }
							</option>
							<option value="active">
								{ __( 'Active', 'saman-seo' ) }
							</option>
							<option value="inactive">
								{ __( 'Inactive', 'saman-seo' ) }
							</option>
						</select>
					</label>
					<label className="filter-field">
						<span>{ __( 'Category', 'saman-seo' ) }</span>
						<select
							value={ filterCategory }
							onChange={ ( e ) =>
								onFilterCategory( e.target.value )
							}
						>
							<option value="">
								{ __( 'All categories', 'saman-seo' ) }
							</option>
							{ categories.map( ( cat ) => (
								<option key={ cat.id } value={ cat.id }>
									{ cat.name }
								</option>
							) ) }
						</select>
					</label>
					{ hasFilters && (
						<button
							type="button"
							className="link-button"
							onClick={ clearFilters }
						>
							{ __( 'Clear', 'saman-seo' ) }
						</button>
					) }
				</div>
			</div>

			{ ! rulesLoading && selectedRules.length > 0 && (
				<div className="bulk-actions">
					<span className="bulk-actions__count">
						{ selectedRules.length }{ ' ' }
						{ __( 'selected', 'saman-seo' ) }
					</span>
					<select
						aria-label={ __( 'Bulk actions', 'saman-seo' ) }
						value={ bulkAction }
						onChange={ ( e ) => onBulkAction( e.target.value ) }
					>
						<option value="">
							{ __( 'Bulk actions', 'saman-seo' ) }
						</option>
						<option value="activate">
							{ __( 'Activate', 'saman-seo' ) }
						</option>
						<option value="deactivate">
							{ __( 'Deactivate', 'saman-seo' ) }
						</option>
						<option value="change_category">
							{ __( 'Change category', 'saman-seo' ) }
						</option>
						<option value="delete">
							{ __( 'Delete', 'saman-seo' ) }
						</option>
					</select>
					{ bulkAction === 'change_category' && (
						<select
							aria-label={ __( 'Category', 'saman-seo' ) }
							value={ bulkCategory }
							onChange={ ( e ) =>
								onBulkCategory( e.target.value )
							}
						>
							<option value="__none__">
								{ __( 'Remove category', 'saman-seo' ) }
							</option>
							{ categories.map( ( cat ) => (
								<option key={ cat.id } value={ cat.id }>
									{ cat.name }
								</option>
							) ) }
						</select>
					) }
					<button
						type="button"
						className="button small"
						onClick={ onApplyBulk }
						disabled={ ! bulkAction }
					>
						{ __( 'Apply', 'saman-seo' ) }
					</button>
				</div>
			) }

			{ content }
		</div>
	);
};

// Categories Tab Component
const CategoriesTab = ( {
	categories,
	templates,
	loading,
	onEdit,
	onDelete,
	onAdd,
} ) => {
	const getTemplateById = ( id ) => templates.find( ( t ) => t.id === id );
	if ( loading ) {
		return (
			<div className="page-body">
				<div className="loading-state">
					{ __( 'Loading categories…', 'saman-seo' ) }
				</div>
			</div>
		);
	}
	return (
		<div className="page-body">
			{ categories.length === 0 ? (
				<div className="empty-state">
					<h3>{ __( 'No categories yet', 'saman-seo' ) }</h3>
					<p>
						{ __(
							'Create categories to organize your linking rules.',
							'saman-seo'
						) }
					</p>
					<button
						type="button"
						className="button primary"
						onClick={ onAdd }
					>
						{ __( 'Add category', 'saman-seo' ) }
					</button>
				</div>
			) : (
				<div className="table-wrap">
					<table className="data-table">
						<thead>
							<tr>
								<th>{ __( 'Name', 'saman-seo' ) }</th>
								<th>{ __( 'Default UTM', 'saman-seo' ) }</th>
								<th className="col-numeric">
									{ __( 'Cap', 'saman-seo' ) }
								</th>
								<th className="col-numeric">
									{ __( 'Rules', 'saman-seo' ) }
								</th>
								<th className="col-actions">
									{ __( 'Actions', 'saman-seo' ) }
								</th>
							</tr>
						</thead>
						<tbody>
							{ categories.map( ( cat ) => {
								const template = getTemplateById(
									cat.default_utm
								);
								return (
									<tr key={ cat.id }>
										<td>
											<div className="category-name">
												<span
													className="color-chip"
													style={ {
														backgroundColor:
															cat.color,
													} }
												/>
												<strong>{ cat.name }</strong>
											</div>
											{ cat.description && (
												<div className="muted">
													{ cat.description }
												</div>
											) }
										</td>
										<td>
											{ template ? (
												template.name
											) : (
												<span className="muted">—</span>
											) }
										</td>
										<td className="numeric-cell">
											{ cat.category_cap || (
												<span className="muted">—</span>
											) }
										</td>
										<td className="numeric-cell">
											{ cat.rule_count || 0 }
										</td>
										<td className="action-cell">
											<div className="table-actions">
												<button
													type="button"
													className="table-actions__secondary"
													onClick={ () =>
														onEdit( cat )
													}
												>
													{ __(
														'Edit',
														'saman-seo'
													) }
												</button>
												<button
													type="button"
													className="table-actions__danger"
													onClick={ () =>
														onDelete( cat.id )
													}
												>
													{ __(
														'Delete',
														'saman-seo'
													) }
												</button>
											</div>
										</td>
									</tr>
								);
							} ) }
						</tbody>
					</table>
				</div>
			) }
		</div>
	);
};

// Templates Tab Component
const TemplatesTab = ( { templates, loading, onEdit, onDelete, onAdd } ) => {
	const applyToLabel = ( value ) => {
		switch ( value ) {
			case 'internal':
				return __( 'Internal only', 'saman-seo' );
			case 'external':
				return __( 'External only', 'saman-seo' );
			default:
				return __( 'Both', 'saman-seo' );
		}
	};
	const appendModeLabel = ( value ) => {
		switch ( value ) {
			case 'always_overwrite':
				return __( 'Always overwrite', 'saman-seo' );
			case 'never':
				return __( 'Never overwrite', 'saman-seo' );
			default:
				return __( 'Append if missing', 'saman-seo' );
		}
	};
	if ( loading ) {
		return (
			<div className="page-body">
				<div className="loading-state">
					{ __( 'Loading templates…', 'saman-seo' ) }
				</div>
			</div>
		);
	}
	return (
		<div className="page-body">
			{ templates.length === 0 ? (
				<div className="empty-state">
					<h3>{ __( 'No UTM templates yet', 'saman-seo' ) }</h3>
					<p>
						{ __(
							'Create templates to add tracking parameters to your links.',
							'saman-seo'
						) }
					</p>
					<button
						type="button"
						className="button primary"
						onClick={ onAdd }
					>
						{ __( 'Add template', 'saman-seo' ) }
					</button>
				</div>
			) : (
				<div className="table-wrap">
					<table className="data-table">
						<thead>
							<tr>
								<th>{ __( 'Name', 'saman-seo' ) }</th>
								<th>{ __( 'utm_source', 'saman-seo' ) }</th>
								<th>{ __( 'utm_medium', 'saman-seo' ) }</th>
								<th>{ __( 'utm_campaign', 'saman-seo' ) }</th>
								<th>{ __( 'Apply to', 'saman-seo' ) }</th>
								<th>{ __( 'Mode', 'saman-seo' ) }</th>
								<th className="col-actions">
									{ __( 'Actions', 'saman-seo' ) }
								</th>
							</tr>
						</thead>
						<tbody>
							{ templates.map( ( tpl ) => (
								<tr key={ tpl.id }>
									<td>
										<strong>{ tpl.name }</strong>
									</td>
									<td>
										{ tpl.utm_source || (
											<span className="muted">—</span>
										) }
									</td>
									<td>
										{ tpl.utm_medium || (
											<span className="muted">—</span>
										) }
									</td>
									<td>
										{ tpl.utm_campaign || (
											<span className="muted">—</span>
										) }
									</td>
									<td>{ applyToLabel( tpl.apply_to ) }</td>
									<td>
										{ appendModeLabel( tpl.append_mode ) }
									</td>
									<td className="action-cell">
										<div className="table-actions">
											<button
												type="button"
												className="table-actions__secondary"
												onClick={ () => onEdit( tpl ) }
											>
												{ __( 'Edit', 'saman-seo' ) }
											</button>
											<button
												type="button"
												className="table-actions__danger"
												onClick={ () =>
													onDelete( tpl.id )
												}
											>
												{ __( 'Delete', 'saman-seo' ) }
											</button>
										</div>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			) }
		</div>
	);
};

// One label + toggle row in the settings tab.
const ToggleRow = ( { id, label, help, checked, onChange } ) => (
	<div className="settings-row">
		<div className="settings-label">
			<label htmlFor={ id }>{ label }</label>
			{ help && <p className="settings-help">{ help }</p> }
		</div>
		<div className="settings-control">
			<label className="toggle">
				<input
					id={ id }
					type="checkbox"
					checked={ !! checked }
					onChange={ ( e ) => onChange( e.target.checked ) }
				/>
				<span className="toggle-track" />
			</label>
		</div>
	</div>
);

// Settings Tab Component
const SettingsTab = ( { settings, onChange } ) => {
	const updateSetting = ( key, value ) => {
		onChange( {
			...settings,
			[ key ]: value,
		} );
	};
	const toggleHeadingLevel = ( level ) => {
		const levels = settings.default_heading_levels || [];
		if ( levels.includes( level ) ) {
			updateSetting(
				'default_heading_levels',
				levels.filter( ( l ) => l !== level )
			);
		} else {
			updateSetting( 'default_heading_levels', [ ...levels, level ] );
		}
	};
	return (
		<div className="page-body">
			<section className="panel">
				<h3>{ __( 'Global defaults', 'saman-seo' ) }</h3>

				<div className="settings-row">
					<div className="settings-label">
						<label htmlFor="default-max-links">
							{ __( 'Default max links per page', 'saman-seo' ) }
						</label>
						<p className="settings-help">
							{ __( '0 means no limit.', 'saman-seo' ) }
						</p>
					</div>
					<div className="settings-control">
						<input
							id="default-max-links"
							className="input--narrow"
							type="number"
							min="0"
							max="50"
							value={ settings.default_max_links_per_page || 0 }
							onChange={ ( e ) =>
								updateSetting(
									'default_max_links_per_page',
									parseInt( e.target.value, 10 ) || 0
								)
							}
						/>
					</div>
				</div>

				<div className="settings-row">
					<div className="settings-label">
						<span className="settings-label__text">
							{ __( 'Default heading behavior', 'saman-seo' ) }
						</span>
						<p className="settings-help">
							{ __(
								'Control whether links can appear in headings.',
								'saman-seo'
							) }
						</p>
					</div>
					<div className="settings-control">
						<div className="radio-group">
							{ [ 'none', 'selected', 'all' ].map( ( opt ) => (
								<label key={ opt } className="radio-item">
									<input
										type="radio"
										name="heading_behavior"
										value={ opt }
										checked={
											settings.default_heading_behavior ===
											opt
										}
										onChange={ () =>
											updateSetting(
												'default_heading_behavior',
												opt
											)
										}
									/>
									<span>
										{ opt.charAt( 0 ).toUpperCase() +
											opt.slice( 1 ) }
									</span>
								</label>
							) ) }
						</div>
						{ settings.default_heading_behavior === 'selected' && (
							<div className="checkbox-group">
								{ HEADING_LEVELS.map( ( level ) => (
									<label
										key={ level }
										className="checkbox-item"
									>
										<input
											type="checkbox"
											checked={ (
												settings.default_heading_levels ||
												[]
											).includes( level ) }
											onChange={ () =>
												toggleHeadingLevel( level )
											}
										/>
										<span>{ level.toUpperCase() }</span>
									</label>
								) ) }
							</div>
						) }
					</div>
				</div>
			</section>

			<section className="panel">
				<h3>{ __( 'Safeties', 'saman-seo' ) }</h3>
				<ToggleRow
					id="avoid-existing"
					label={ __(
						'Avoid replacing inside existing links',
						'saman-seo'
					) }
					checked={ settings.avoid_existing_links }
					onChange={ ( value ) =>
						updateSetting( 'avoid_existing_links', value )
					}
				/>
				<ToggleRow
					id="word-boundaries"
					label={ __( 'Prefer word boundaries', 'saman-seo' ) }
					checked={ settings.prefer_word_boundaries }
					onChange={ ( value ) =>
						updateSetting( 'prefer_word_boundaries', value )
					}
				/>
				<ToggleRow
					id="normalize-accents"
					label={ __( 'Normalize accents/diacritics', 'saman-seo' ) }
					checked={ settings.normalize_accents }
					onChange={ ( value ) =>
						updateSetting( 'normalize_accents', value )
					}
				/>
			</section>

			<section className="panel">
				<h3>{ __( 'Performance', 'saman-seo' ) }</h3>
				<ToggleRow
					id="cache-content"
					label={ __( 'Cache rendered content', 'saman-seo' ) }
					checked={ settings.cache_rendered_content }
					onChange={ ( value ) =>
						updateSetting( 'cache_rendered_content', value )
					}
				/>
				<ToggleRow
					id="chunk-docs"
					label={ __( 'Chunk long documents', 'saman-seo' ) }
					help={ __(
						'Prevents timeouts on large content.',
						'saman-seo'
					) }
					checked={ settings.chunk_long_documents }
					onChange={ ( value ) =>
						updateSetting( 'chunk_long_documents', value )
					}
				/>
			</section>
		</div>
	);
};

// Rule Modal Component - Simplified
const RuleModal = ( { rule, categories, templates, onClose, onSave } ) => {
	const isEdit = !! rule;
	const [ formData, setFormData ] = useState( () => {
		if ( rule ) {
			return {
				...rule,
			};
		}
		return {
			title: '',
			category: '',
			keywords: [],
			destination: {
				type: 'post',
				post: 0,
				url: '',
			},
			utm_template: 'inherit',
			attributes: {
				nofollow: false,
				new_tab: false,
			},
			limits: {
				max_page: 1,
			},
			status: 'active',
		};
	} );
	const [ keywordInput, setKeywordInput ] = useState( '' );
	const [ postSearchQuery, setPostSearchQuery ] = useState( '' );
	const [ postSearchResults, setPostSearchResults ] = useState( [] );
	const [ selectedPost, setSelectedPost ] = useState( null );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( '' );
	useEffect( () => {
		if (
			rule &&
			rule.destination?.type === 'post' &&
			rule.destination?.post
		) {
			setSelectedPost( {
				id: rule.destination.post,
				title:
					rule.destination_label ||
					__( 'Loading\u2026', 'saman-seo' ),
			} );
		}
	}, [ rule ] );
	useEffect( () => {
		if ( postSearchQuery.length < 2 ) {
			setPostSearchResults( [] );
			return;
		}
		const timer = setTimeout( async () => {
			try {
				const res = await apiFetch( {
					path: `/saman-seo/v1/internal-links/search-posts?search=${ encodeURIComponent(
						postSearchQuery
					) }`,
				} );
				if ( res.success ) setPostSearchResults( res.data );
			} catch ( err ) {
				console.error( 'Post search failed:', err );
			}
		}, 300 );
		return () => clearTimeout( timer );
	}, [ postSearchQuery ] );
	const updateFormData = ( key, value ) =>
		setFormData( ( prev ) => ( {
			...prev,
			[ key ]: value,
		} ) );
	const updateNested = ( parent, key, value ) =>
		setFormData( ( prev ) => ( {
			...prev,
			[ parent ]: {
				...prev[ parent ],
				[ key ]: value,
			},
		} ) );
	const addKeyword = () => {
		const keyword = keywordInput.trim();
		if ( keyword && ! formData.keywords.includes( keyword ) ) {
			updateFormData( 'keywords', [ ...formData.keywords, keyword ] );
			setKeywordInput( '' );
		}
	};
	const removeKeyword = ( keyword ) =>
		updateFormData(
			'keywords',
			formData.keywords.filter( ( k ) => k !== keyword )
		);
	const handleKeywordKeyDown = ( e ) => {
		if ( e.key === 'Enter' ) {
			e.preventDefault();
			addKeyword();
		}
	};
	const selectPost = ( post ) => {
		setSelectedPost( post );
		updateNested( 'destination', 'post', post.id );
		updateNested( 'destination', 'type', 'post' );
		setPostSearchQuery( '' );
		setPostSearchResults( [] );
	};
	const handleSubmit = async ( e ) => {
		e.preventDefault();
		setError( '' );
		setSaving( true );
		try {
			const path = isEdit
				? `/saman-seo/v1/internal-links/rules/${ rule.id }`
				: '/saman-seo/v1/internal-links/rules';
			const res = await apiFetch( {
				path,
				method: isEdit ? 'PUT' : 'POST',
				data: formData,
			} );
			if ( res.success ) {
				onSave( res.data );
			} else {
				setError(
					res.message || __( 'Failed to save rule', 'saman-seo' )
				);
			}
		} catch ( err ) {
			setError( err.message || __( 'Failed to save rule', 'saman-seo' ) );
		} finally {
			setSaving( false );
		}
	};
	return (
		<div className="modal-overlay" onClick={ onClose }>
			<div
				className="modal modal--large internal-link-modal"
				onClick={ ( e ) => e.stopPropagation() }
			>
				<div className="modal__header">
					<h2>
						{ isEdit
							? __( 'Edit rule', 'saman-seo' )
							: __( 'Add rule', 'saman-seo' ) }
					</h2>
					<button
						type="button"
						className="modal__close"
						aria-label={ __( 'Close', 'saman-seo' ) }
						onClick={ onClose }
					>
						&times;
					</button>
				</div>

				<form onSubmit={ handleSubmit }>
					<div className="modal__body">
						{ error && <div className="form-error">{ error }</div> }

						<div className="form-field">
							<label htmlFor="rule-title">
								{ __( 'Title', 'saman-seo' ) }
							</label>
							<input
								id="rule-title"
								type="text"
								value={ formData.title }
								onChange={ ( e ) =>
									updateFormData( 'title', e.target.value )
								}
								placeholder={ __(
									'e.g., Link to Services page',
									'saman-seo'
								) }
								required
							/>
						</div>

						<div className="form-row">
							<div className="form-field">
								<label htmlFor="rule-category">
									{ __( 'Category', 'saman-seo' ) }
								</label>
								<select
									id="rule-category"
									value={ formData.category }
									onChange={ ( e ) =>
										updateFormData(
											'category',
											e.target.value
										)
									}
								>
									<option value="">
										{ __( 'None', 'saman-seo' ) }
									</option>
									{ categories.map( ( cat ) => (
										<option key={ cat.id } value={ cat.id }>
											{ cat.name }
										</option>
									) ) }
								</select>
							</div>
							<div className="form-field">
								<label>{ __( 'Status', 'saman-seo' ) }</label>
								<label className="toggle">
									<input
										type="checkbox"
										checked={ formData.status === 'active' }
										onChange={ ( e ) =>
											updateFormData(
												'status',
												e.target.checked
													? 'active'
													: 'inactive'
											)
										}
									/>
									<span className="toggle-track" />
								</label>
							</div>
						</div>

						<div className="form-field">
							<label>{ __( 'Destination', 'saman-seo' ) }</label>
							<div
								className="radio-group"
								style={ {
									marginBottom: '12px',
								} }
							>
								<label className="radio-item">
									<input
										type="radio"
										name="destination_type"
										checked={
											formData.destination.type === 'post'
										}
										onChange={ () =>
											updateNested(
												'destination',
												'type',
												'post'
											)
										}
									/>
									<span>
										{ __( 'Post/Page', 'saman-seo' ) }
									</span>
								</label>
								<label className="radio-item">
									<input
										type="radio"
										name="destination_type"
										checked={
											formData.destination.type === 'url'
										}
										onChange={ () =>
											updateNested(
												'destination',
												'type',
												'url'
											)
										}
									/>
									<span>
										{ __( 'Custom URL', 'saman-seo' ) }
									</span>
								</label>
							</div>
							{ formData.destination.type === 'post' ? (
								selectedPost ? (
									<div className="selected-post">
										<span>{ selectedPost.title }</span>
										<button
											type="button"
											className="link-button"
											onClick={ () => {
												setSelectedPost( null );
												updateNested(
													'destination',
													'post',
													0
												);
											} }
										>
											{ __( 'Change', 'saman-seo' ) }
										</button>
									</div>
								) : (
									<div className="post-search">
										<input
											type="text"
											placeholder={ __(
												'Search posts\u2026',
												'saman-seo'
											) }
											value={ postSearchQuery }
											onChange={ ( e ) =>
												setPostSearchQuery(
													e.target.value
												)
											}
										/>
										{ postSearchResults.length > 0 && (
											<ul className="post-search__results">
												{ postSearchResults.map(
													( post ) => (
														<li key={ post.id }>
															<button
																type="button"
																onClick={ () =>
																	selectPost(
																		post
																	)
																}
															>
																{ post.title }{ ' ' }
																<span className="muted">
																	(
																	{
																		post.post_type
																	}
																	)
																</span>
															</button>
														</li>
													)
												) }
											</ul>
										) }
									</div>
								)
							) : (
								<input
									type="url"
									value={ formData.destination.url }
									onChange={ ( e ) =>
										updateNested(
											'destination',
											'url',
											e.target.value
										)
									}
									placeholder={ __(
										'https://example.com',
										'saman-seo'
									) }
								/>
							) }
						</div>

						<div className="form-field">
							<label>{ __( 'Keywords', 'saman-seo' ) }</label>
							<div className="tag-input">
								<div className="tag-input__tags">
									{ formData.keywords.map( ( keyword ) => (
										<span key={ keyword } className="tag">
											{ keyword }
											<button
												type="button"
												onClick={ () =>
													removeKeyword( keyword )
												}
											>
												&times;
											</button>
										</span>
									) ) }
								</div>
								<input
									type="text"
									placeholder={ __(
										'Type and press Enter',
										'saman-seo'
									) }
									value={ keywordInput }
									onChange={ ( e ) =>
										setKeywordInput( e.target.value )
									}
									onKeyDown={ handleKeywordKeyDown }
									onBlur={ addKeyword }
								/>
							</div>
						</div>

						<div className="form-row">
							<div className="form-field narrow">
								<label htmlFor="max-page">
									{ __( 'Max per page', 'saman-seo' ) }
								</label>
								<input
									id="max-page"
									type="number"
									min="0"
									max="50"
									value={ formData.limits?.max_page || 1 }
									onChange={ ( e ) =>
										updateNested(
											'limits',
											'max_page',
											parseInt( e.target.value, 10 ) || 1
										)
									}
								/>
							</div>
							<div className="form-field">
								<label>{ __( 'Options', 'saman-seo' ) }</label>
								<div className="checkbox-group">
									<label className="checkbox-item">
										<input
											type="checkbox"
											checked={
												formData.attributes?.nofollow
											}
											onChange={ ( e ) =>
												updateNested(
													'attributes',
													'nofollow',
													e.target.checked
												)
											}
										/>
										<span>
											{ __( 'nofollow', 'saman-seo' ) }
										</span>
									</label>
									<label className="checkbox-item">
										<input
											type="checkbox"
											checked={
												formData.attributes?.new_tab
											}
											onChange={ ( e ) =>
												updateNested(
													'attributes',
													'new_tab',
													e.target.checked
												)
											}
										/>
										<span>
											{ __( 'New tab', 'saman-seo' ) }
										</span>
									</label>
								</div>
							</div>
						</div>
					</div>

					<div className="modal__footer">
						<button
							type="button"
							className="button ghost"
							onClick={ onClose }
						>
							{ __( 'Cancel', 'saman-seo' ) }
						</button>
						<button
							type="submit"
							className="button primary"
							disabled={ saving }
						>
							{ saving
								? __( 'Saving\u2026', 'saman-seo' )
								: isEdit
								? __( 'Update', 'saman-seo' )
								: __( 'Create', 'saman-seo' ) }
						</button>
					</div>
				</form>
			</div>
		</div>
	);
};

// Category Modal Component
const CategoryModal = ( { category, templates, onClose, onSave } ) => {
	const isEdit = !! category;
	const [ formData, setFormData ] = useState( () => {
		if ( category ) {
			return {
				...category,
			};
		}
		return {
			name: '',
			color: '#4F46E5',
			description: '',
			default_utm: '',
			category_cap: 0,
		};
	} );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( '' );
	const handleSubmit = async ( e ) => {
		e.preventDefault();
		setError( '' );
		setSaving( true );
		try {
			const path = isEdit
				? `/saman-seo/v1/internal-links/categories/${ category.id }`
				: '/saman-seo/v1/internal-links/categories';
			const method = isEdit ? 'PUT' : 'POST';
			const res = await apiFetch( {
				path,
				method,
				data: formData,
			} );
			if ( res.success ) {
				onSave( res.data );
			} else {
				setError(
					res.message || __( 'Failed to save category', 'saman-seo' )
				);
			}
		} catch ( err ) {
			setError(
				err.message || __( 'Failed to save category', 'saman-seo' )
			);
		} finally {
			setSaving( false );
		}
	};
	return (
		<div className="modal-overlay" onClick={ onClose }>
			<div
				className="modal internal-link-modal"
				onClick={ ( e ) => e.stopPropagation() }
			>
				<div className="modal__header">
					<h2>
						{ isEdit
							? __( 'Edit category', 'saman-seo' )
							: __( 'Add category', 'saman-seo' ) }
					</h2>
					<button
						type="button"
						className="modal__close"
						aria-label={ __( 'Close', 'saman-seo' ) }
						onClick={ onClose }
					>
						&times;
					</button>
				</div>

				<form onSubmit={ handleSubmit }>
					<div className="modal__body">
						{ error && <div className="form-error">{ error }</div> }

						<div className="form-field">
							<label htmlFor="cat-name">
								{ __( 'Name', 'saman-seo' ) }
							</label>
							<input
								id="cat-name"
								type="text"
								value={ formData.name }
								onChange={ ( e ) =>
									setFormData( {
										...formData,
										name: e.target.value,
									} )
								}
								required
							/>
						</div>

						<div className="form-field">
							<label htmlFor="cat-color">
								{ __( 'Color', 'saman-seo' ) }
							</label>
							<input
								id="cat-color"
								type="color"
								value={ formData.color }
								onChange={ ( e ) =>
									setFormData( {
										...formData,
										color: e.target.value,
									} )
								}
							/>
						</div>

						<div className="form-field">
							<label htmlFor="cat-desc">
								{ __( 'Description', 'saman-seo' ) }
							</label>
							<textarea
								id="cat-desc"
								rows="3"
								value={ formData.description }
								onChange={ ( e ) =>
									setFormData( {
										...formData,
										description: e.target.value,
									} )
								}
							/>
						</div>

						<div className="form-field">
							<label htmlFor="cat-utm">
								{ __( 'Default UTM template', 'saman-seo' ) }
							</label>
							<select
								id="cat-utm"
								value={ formData.default_utm }
								onChange={ ( e ) =>
									setFormData( {
										...formData,
										default_utm: e.target.value,
									} )
								}
							>
								<option value="">
									{ __( 'None', 'saman-seo' ) }
								</option>
								{ templates.map( ( tpl ) => (
									<option key={ tpl.id } value={ tpl.id }>
										{ tpl.name }
									</option>
								) ) }
							</select>
						</div>

						<div className="form-field">
							<label htmlFor="cat-cap">
								{ __(
									'Category-level cap (per page)',
									'saman-seo'
								) }
							</label>
							<input
								id="cat-cap"
								type="number"
								min="0"
								max="50"
								value={ formData.category_cap || '' }
								onChange={ ( e ) =>
									setFormData( {
										...formData,
										category_cap:
											parseInt( e.target.value, 10 ) || 0,
									} )
								}
								placeholder={ __(
									'0 = no extra cap',
									'saman-seo'
								) }
							/>
						</div>
					</div>

					<div className="modal__footer">
						<button
							type="button"
							className="button ghost"
							onClick={ onClose }
						>
							{ __( 'Cancel', 'saman-seo' ) }
						</button>
						<button
							type="submit"
							className="button primary"
							disabled={ saving }
						>
							{ saving
								? __( 'Saving\u2026', 'saman-seo' )
								: isEdit
								? __( 'Update category', 'saman-seo' )
								: __( 'Create category', 'saman-seo' ) }
						</button>
					</div>
				</form>
			</div>
		</div>
	);
};

// Template Modal Component - Simplified
const TemplateModal = ( { template, onClose, onSave } ) => {
	const isEdit = !! template;
	const [ formData, setFormData ] = useState( () =>
		template
			? {
					...template,
			  }
			: {
					name: '',
					utm_source: '',
					utm_medium: '',
					utm_campaign: '',
					apply_to: 'both',
			  }
	);
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( '' );
	const handleSubmit = async ( e ) => {
		e.preventDefault();
		setError( '' );
		setSaving( true );
		try {
			const path = isEdit
				? `/saman-seo/v1/internal-links/templates/${ template.id }`
				: '/saman-seo/v1/internal-links/templates';
			const res = await apiFetch( {
				path,
				method: isEdit ? 'PUT' : 'POST',
				data: formData,
			} );
			if ( res.success ) {
				onSave( res.data );
			} else {
				setError(
					res.message || __( 'Failed to save template', 'saman-seo' )
				);
			}
		} catch ( err ) {
			setError(
				err.message || __( 'Failed to save template', 'saman-seo' )
			);
		} finally {
			setSaving( false );
		}
	};
	return (
		<div className="modal-overlay" onClick={ onClose }>
			<div
				className="modal internal-link-modal"
				onClick={ ( e ) => e.stopPropagation() }
			>
				<div className="modal__header">
					<h2>
						{ isEdit
							? __( 'Edit template', 'saman-seo' )
							: __( 'Add template', 'saman-seo' ) }
					</h2>
					<button
						type="button"
						className="modal__close"
						aria-label={ __( 'Close', 'saman-seo' ) }
						onClick={ onClose }
					>
						&times;
					</button>
				</div>

				<form onSubmit={ handleSubmit }>
					<div className="modal__body">
						{ error && <div className="form-error">{ error }</div> }

						<div className="form-field">
							<label htmlFor="tpl-name">
								{ __( 'Name', 'saman-seo' ) }
							</label>
							<input
								id="tpl-name"
								type="text"
								value={ formData.name }
								onChange={ ( e ) =>
									setFormData( {
										...formData,
										name: e.target.value,
									} )
								}
								required
							/>
						</div>

						<div className="form-row">
							<div className="form-field">
								<label htmlFor="tpl-source">
									{ __( 'utm_source', 'saman-seo' ) }
								</label>
								<input
									id="tpl-source"
									type="text"
									value={ formData.utm_source || '' }
									onChange={ ( e ) =>
										setFormData( {
											...formData,
											utm_source: e.target.value,
										} )
									}
									placeholder={ __(
										'e.g., website',
										'saman-seo'
									) }
								/>
							</div>
							<div className="form-field">
								<label htmlFor="tpl-medium">
									{ __( 'utm_medium', 'saman-seo' ) }
								</label>
								<input
									id="tpl-medium"
									type="text"
									value={ formData.utm_medium || '' }
									onChange={ ( e ) =>
										setFormData( {
											...formData,
											utm_medium: e.target.value,
										} )
									}
									placeholder={ __(
										'e.g., internal_link',
										'saman-seo'
									) }
								/>
							</div>
						</div>

						<div className="form-field">
							<label htmlFor="tpl-campaign">
								{ __( 'utm_campaign', 'saman-seo' ) }
							</label>
							<input
								id="tpl-campaign"
								type="text"
								value={ formData.utm_campaign || '' }
								onChange={ ( e ) =>
									setFormData( {
										...formData,
										utm_campaign: e.target.value,
									} )
								}
								placeholder={ __(
									'e.g., cross_selling',
									'saman-seo'
								) }
							/>
						</div>

						<div className="form-field">
							<label>{ __( 'Apply to', 'saman-seo' ) }</label>
							<div className="radio-group">
								<label className="radio-item">
									<input
										type="radio"
										name="apply_to"
										checked={
											formData.apply_to === 'internal'
										}
										onChange={ () =>
											setFormData( {
												...formData,
												apply_to: 'internal',
											} )
										}
									/>
									<span>
										{ __( 'Internal', 'saman-seo' ) }
									</span>
								</label>
								<label className="radio-item">
									<input
										type="radio"
										name="apply_to"
										checked={
											formData.apply_to === 'external'
										}
										onChange={ () =>
											setFormData( {
												...formData,
												apply_to: 'external',
											} )
										}
									/>
									<span>
										{ __( 'External', 'saman-seo' ) }
									</span>
								</label>
								<label className="radio-item">
									<input
										type="radio"
										name="apply_to"
										checked={ formData.apply_to === 'both' }
										onChange={ () =>
											setFormData( {
												...formData,
												apply_to: 'both',
											} )
										}
									/>
									<span>{ __( 'Both', 'saman-seo' ) }</span>
								</label>
							</div>
						</div>
					</div>

					<div className="modal__footer">
						<button
							type="button"
							className="button ghost"
							onClick={ onClose }
						>
							{ __( 'Cancel', 'saman-seo' ) }
						</button>
						<button
							type="submit"
							className="button primary"
							disabled={ saving }
						>
							{ saving
								? __( 'Saving\u2026', 'saman-seo' )
								: isEdit
								? __( 'Update', 'saman-seo' )
								: __( 'Create', 'saman-seo' ) }
						</button>
					</div>
				</form>
			</div>
		</div>
	);
};
export default InternalLinking;
