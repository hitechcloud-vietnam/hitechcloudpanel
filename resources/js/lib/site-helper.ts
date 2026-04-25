import { Site } from '@/types/site';

const siteHelper = {
  getStoredSite() {
    const storedSite = localStorage.getItem('site');
    if (storedSite) {
      return JSON.parse(storedSite) as Site;
    }
    return null;
  },
  storeSite(site?: Site) {
    if (!site) {
      localStorage.removeItem('site');
      return;
    }
    localStorage.setItem('site', JSON.stringify(site));
  },
};

export default siteHelper;
