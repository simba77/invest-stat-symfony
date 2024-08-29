import axios from "axios";

export function useDashboard() {
  function getDashboard() {
    return axios.get('/api/dashboard');
  }

  return {
    getDashboard
  }
}
