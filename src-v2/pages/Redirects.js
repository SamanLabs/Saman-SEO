import SubTabs from '../components/SubTabs';
import useUrlTab from '../hooks/useUrlTab';

const redirectsTabs = [
    { id: 'redirects', label: 'Redirects' },
    { id: '404-log', label: '404 Log' },
];

const Redirects = () => {
    const [activeTab, setActiveTab] = useUrlTab({ tabs: redirectsTabs, defaultTab: 'redirects' });

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Redirects</h1>
                    <p>Manage 301 redirects and monitor 404 errors to improve user experience.</p>
                </div>
                <button type="button" className="button ghost">Import Redirects</button>
            </div>

            <SubTabs tabs={redirectsTabs} activeTab={activeTab} onChange={setActiveTab} ariaLabel="Redirects sections" />

            <section className="panel">
                {activeTab === 'redirects' ? (
                    <>
                        <div className="table-toolbar">
                            <div>
                                <h3>Active Redirects</h3>
                                <p className="muted">Manage your 301 redirect rules.</p>
                            </div>
                            <div className="inline-form">
                                <input type="text" placeholder="Source URL" />
                                <input type="text" placeholder="Destination URL" />
                                <button type="button" className="button primary">Add Redirect</button>
                            </div>
                        </div>
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Source URL</th>
                                    <th>Destination URL</th>
                                    <th>Hits</th>
                                    <th>Last Hit</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>/old-page/</td>
                                    <td>/new-page/</td>
                                    <td>124</td>
                                    <td>Today, 14:32</td>
                                    <td><button type="button" className="link-button">Remove</button></td>
                                </tr>
                                <tr>
                                    <td>/blog/2023/post/</td>
                                    <td>/articles/post/</td>
                                    <td>89</td>
                                    <td>Yesterday, 09:15</td>
                                    <td><button type="button" className="link-button">Remove</button></td>
                                </tr>
                                <tr>
                                    <td>/contact-us/</td>
                                    <td>/contact/</td>
                                    <td>56</td>
                                    <td>Sep 21, 18:42</td>
                                    <td><button type="button" className="link-button">Remove</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </>
                ) : (
                    <>
                        <div className="table-toolbar">
                            <div>
                                <h3>404 Error Log</h3>
                                <p className="muted">Monitor broken links and create redirects to fix them.</p>
                            </div>
                            <button type="button" className="button ghost">Clear Log</button>
                        </div>
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>URL</th>
                                    <th>Hits</th>
                                    <th>Last Seen</th>
                                    <th>Referrer</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>/wp-content/old-image.jpg</td>
                                    <td>42</td>
                                    <td>Today, 11:20</td>
                                    <td>Google</td>
                                    <td><button type="button" className="link-button">Create Redirect</button></td>
                                </tr>
                                <tr>
                                    <td>/category/deleted/</td>
                                    <td>18</td>
                                    <td>Yesterday, 16:45</td>
                                    <td>Direct</td>
                                    <td><button type="button" className="link-button">Create Redirect</button></td>
                                </tr>
                                <tr>
                                    <td>/product/discontinued/</td>
                                    <td>8</td>
                                    <td>Sep 20, 08:30</td>
                                    <td>Bing</td>
                                    <td><button type="button" className="link-button">Create Redirect</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </>
                )}
            </section>
        </div>
    );
};

export default Redirects;
