import SubTabs from '../components/SubTabs';
import useUrlTab from '../hooks/useUrlTab';

const linkingTabs = [
    { id: 'rules', label: 'Rules' },
    { id: 'categories', label: 'Categories' },
    { id: 'settings', label: 'Settings' },
];

const InternalLinking = () => {
    const [activeTab, setActiveTab] = useUrlTab({ tabs: linkingTabs, defaultTab: 'rules' });

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Internal Linking</h1>
                    <p>Automatically add internal links to your content based on keywords.</p>
                </div>
                <button type="button" className="button ghost">Export Rules</button>
            </div>

            <SubTabs tabs={linkingTabs} activeTab={activeTab} onChange={setActiveTab} ariaLabel="Internal linking sections" />

            <section className="panel">
                {activeTab === 'rules' ? (
                    <>
                        <div className="table-toolbar">
                            <div>
                                <h3>Linking Rules</h3>
                                <p className="muted">Define keywords and their target URLs for automatic linking.</p>
                            </div>
                            <div className="inline-form">
                                <input type="text" placeholder="Keyword" />
                                <input type="text" placeholder="Target URL" />
                                <button type="button" className="button primary">Add Rule</button>
                            </div>
                        </div>
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Keyword</th>
                                    <th>Target URL</th>
                                    <th>Category</th>
                                    <th>Matches</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>SEO optimization</td>
                                    <td>/services/seo/</td>
                                    <td><span className="pill">Services</span></td>
                                    <td>24</td>
                                    <td><button type="button" className="link-button">Remove</button></td>
                                </tr>
                                <tr>
                                    <td>contact us</td>
                                    <td>/contact/</td>
                                    <td><span className="pill">General</span></td>
                                    <td>12</td>
                                    <td><button type="button" className="link-button">Remove</button></td>
                                </tr>
                                <tr>
                                    <td>WordPress</td>
                                    <td>/blog/wordpress-guide/</td>
                                    <td><span className="pill">Blog</span></td>
                                    <td>45</td>
                                    <td><button type="button" className="link-button">Remove</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </>
                ) : activeTab === 'categories' ? (
                    <>
                        <div className="table-toolbar">
                            <div>
                                <h3>Rule Categories</h3>
                                <p className="muted">Organize your linking rules into categories.</p>
                            </div>
                            <button type="button" className="button primary">Add Category</button>
                        </div>
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Rules Count</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Services</td>
                                    <td>8</td>
                                    <td><span className="pill success">Active</span></td>
                                    <td><button type="button" className="link-button">Edit</button></td>
                                </tr>
                                <tr>
                                    <td>Blog</td>
                                    <td>15</td>
                                    <td><span className="pill success">Active</span></td>
                                    <td><button type="button" className="link-button">Edit</button></td>
                                </tr>
                                <tr>
                                    <td>General</td>
                                    <td>5</td>
                                    <td><span className="pill success">Active</span></td>
                                    <td><button type="button" className="link-button">Edit</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </>
                ) : (
                    <>
                        <div className="table-toolbar">
                            <div>
                                <h3>Linking Settings</h3>
                                <p className="muted">Configure how internal links are applied to your content.</p>
                            </div>
                        </div>
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label htmlFor="enable-linking">Enable Auto-Linking</label>
                                <p className="settings-help">Automatically add links based on your rules.</p>
                            </div>
                            <div className="settings-control">
                                <label className="toggle">
                                    <input id="enable-linking" type="checkbox" defaultChecked />
                                    <span className="toggle-track" />
                                    <span className="toggle-text">Enabled</span>
                                </label>
                            </div>
                        </div>
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label htmlFor="max-links">Max Links Per Post</label>
                                <p className="settings-help">Maximum auto-links to add per post.</p>
                            </div>
                            <div className="settings-control">
                                <input id="max-links" type="number" defaultValue="3" style={{ width: '80px' }} />
                            </div>
                        </div>
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label htmlFor="open-new-tab">Open in New Tab</label>
                                <p className="settings-help">Open auto-generated links in a new browser tab.</p>
                            </div>
                            <div className="settings-control">
                                <label className="toggle">
                                    <input id="open-new-tab" type="checkbox" />
                                    <span className="toggle-track" />
                                    <span className="toggle-text">Disabled</span>
                                </label>
                            </div>
                        </div>
                    </>
                )}
            </section>
        </div>
    );
};

export default InternalLinking;
