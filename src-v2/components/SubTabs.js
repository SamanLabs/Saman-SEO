const SubTabs = ({ tabs, activeTab, onChange, ariaLabel }) => {
    return (
        <div className="sub-tabs" role="tablist" aria-label={ariaLabel}>
            {tabs.map((tab) => (
                <button
                    key={tab.id}
                    type="button"
                    role="tab"
                    className={`sub-tab ${activeTab === tab.id ? 'is-active' : ''}`}
                    aria-selected={activeTab === tab.id}
                    onClick={() => onChange(tab.id)}
                >
                    {tab.label}
                </button>
            ))}
        </div>
    );
};

export default SubTabs;
