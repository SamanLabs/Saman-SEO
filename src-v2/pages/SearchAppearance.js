import SubTabs from '../components/SubTabs';
import useUrlTab from '../hooks/useUrlTab';

const searchAppearanceTabs = [
    { id: 'global', label: 'Global' },
    { id: 'content-types', label: 'Content Types' },
    { id: 'taxonomies', label: 'Taxonomies' },
    { id: 'archives', label: 'Archives' },
    { id: 'social', label: 'Social' },
];

const SearchAppearance = () => {
    const [activeTab, setActiveTab] = useUrlTab({ tabs: searchAppearanceTabs, defaultTab: 'global' });

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Search Appearance</h1>
                    <p>Control how your content appears in search results and social shares.</p>
                </div>
                <button type="button" className="button ghost">Preview in Search</button>
            </div>

            <SubTabs tabs={searchAppearanceTabs} activeTab={activeTab} onChange={setActiveTab} ariaLabel="Search appearance sections" />

            <section className="panel">
                {activeTab === 'global' && (
                    <>
                        <div className="table-toolbar">
                            <div>
                                <h3>Homepage SEO</h3>
                                <p className="muted">Configure default title and meta description for your homepage.</p>
                            </div>
                        </div>
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label htmlFor="home-title">Homepage Title</label>
                                <p className="settings-help">The title tag for your homepage.</p>
                            </div>
                            <div className="settings-control">
                                <input id="home-title" type="text" placeholder="Your Site Name - Tagline" />
                            </div>
                        </div>
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label htmlFor="home-desc">Meta Description</label>
                                <p className="settings-help">A brief description of your website (150-160 chars).</p>
                            </div>
                            <div className="settings-control">
                                <textarea id="home-desc" rows="3" placeholder="A brief description of your website..." />
                            </div>
                        </div>
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label htmlFor="title-separator">Title Separator</label>
                                <p className="settings-help">Character used between title parts.</p>
                            </div>
                            <div className="settings-control">
                                <select id="title-separator">
                                    <option value="-">Dash (-)</option>
                                    <option value="|">Pipe (|)</option>
                                    <option value="•">Bullet (•)</option>
                                    <option value="»">Arrow (»)</option>
                                </select>
                            </div>
                        </div>
                    </>
                )}
                {activeTab === 'content-types' && (
                    <>
                        <div className="table-toolbar">
                            <div>
                                <h3>Content Types</h3>
                                <p className="muted">Configure SEO defaults for each post type.</p>
                            </div>
                        </div>
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Post Type</th>
                                    <th>Title Template</th>
                                    <th>Show in Search</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Posts</td>
                                    <td><code>%title% %sep% %sitename%</code></td>
                                    <td><span className="pill success">Yes</span></td>
                                    <td><button type="button" className="link-button">Edit</button></td>
                                </tr>
                                <tr>
                                    <td>Pages</td>
                                    <td><code>%title% %sep% %sitename%</code></td>
                                    <td><span className="pill success">Yes</span></td>
                                    <td><button type="button" className="link-button">Edit</button></td>
                                </tr>
                                <tr>
                                    <td>Media</td>
                                    <td><code>%title% %sep% %sitename%</code></td>
                                    <td><span className="pill warning">No</span></td>
                                    <td><button type="button" className="link-button">Edit</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </>
                )}
                {activeTab === 'taxonomies' && (
                    <>
                        <div className="table-toolbar">
                            <div>
                                <h3>Taxonomies</h3>
                                <p className="muted">Configure SEO settings for categories, tags, and custom taxonomies.</p>
                            </div>
                        </div>
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Taxonomy</th>
                                    <th>Title Template</th>
                                    <th>Show in Search</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Categories</td>
                                    <td><code>%term_title% Archives %sep% %sitename%</code></td>
                                    <td><span className="pill success">Yes</span></td>
                                    <td><button type="button" className="link-button">Edit</button></td>
                                </tr>
                                <tr>
                                    <td>Tags</td>
                                    <td><code>%term_title% Archives %sep% %sitename%</code></td>
                                    <td><span className="pill warning">No</span></td>
                                    <td><button type="button" className="link-button">Edit</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </>
                )}
                {activeTab === 'archives' && (
                    <>
                        <div className="table-toolbar">
                            <div>
                                <h3>Archives</h3>
                                <p className="muted">Configure SEO for date, author, and other archive pages.</p>
                            </div>
                        </div>
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label htmlFor="author-archives">Author Archives</label>
                                <p className="settings-help">Enable SEO for author archive pages.</p>
                            </div>
                            <div className="settings-control">
                                <label className="toggle">
                                    <input id="author-archives" type="checkbox" defaultChecked />
                                    <span className="toggle-track" />
                                    <span className="toggle-text">Enabled</span>
                                </label>
                            </div>
                        </div>
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label htmlFor="date-archives">Date Archives</label>
                                <p className="settings-help">Enable SEO for date-based archive pages.</p>
                            </div>
                            <div className="settings-control">
                                <label className="toggle">
                                    <input id="date-archives" type="checkbox" />
                                    <span className="toggle-track" />
                                    <span className="toggle-text">Disabled</span>
                                </label>
                            </div>
                        </div>
                    </>
                )}
                {activeTab === 'social' && (
                    <>
                        <div className="table-toolbar">
                            <div>
                                <h3>Social Settings</h3>
                                <p className="muted">Configure Open Graph and Twitter Card defaults.</p>
                            </div>
                        </div>
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label htmlFor="og-default-image">Default Social Image</label>
                                <p className="settings-help">Fallback image for posts without featured images.</p>
                            </div>
                            <div className="settings-control">
                                <button type="button" className="button ghost">Select Image</button>
                            </div>
                        </div>
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label htmlFor="twitter-card-type">Twitter Card Type</label>
                                <p className="settings-help">Choose the default Twitter card format.</p>
                            </div>
                            <div className="settings-control">
                                <select id="twitter-card-type">
                                    <option value="summary">Summary</option>
                                    <option value="summary_large_image">Summary Large Image</option>
                                </select>
                            </div>
                        </div>
                    </>
                )}
            </section>
        </div>
    );
};

export default SearchAppearance;
