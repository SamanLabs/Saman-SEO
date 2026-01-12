import { useEffect, useState } from 'react';

const getTabFromUrl = (tabs, defaultTab, paramName) => {
    if (typeof window === 'undefined') {
        return defaultTab;
    }

    const url = new URL(window.location.href);
    const tab = url.searchParams.get(paramName);
    if (tab && tabs.some((item) => item.id === tab)) {
        return tab;
    }

    return defaultTab;
};

const useUrlTab = ({ tabs, defaultTab, paramName = 'tab' }) => {
    const [activeTab, setActiveTab] = useState(() => getTabFromUrl(tabs, defaultTab, paramName));

    const updateTab = (tabId) => {
        if (!tabs.some((item) => item.id === tabId)) {
            return;
        }

        setActiveTab(tabId);

        if (typeof window === 'undefined') {
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.set(paramName, tabId);
        window.history.replaceState({}, '', url.toString());
    };

    useEffect(() => {
        const handlePopState = () => {
            setActiveTab(getTabFromUrl(tabs, defaultTab, paramName));
        };

        window.addEventListener('popstate', handlePopState);
        return () => window.removeEventListener('popstate', handlePopState);
    }, [tabs, defaultTab, paramName]);

    return [activeTab, updateTab];
};

export default useUrlTab;
