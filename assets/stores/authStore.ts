import {defineStore} from 'pinia'
import axios from "axios";

export const authStore = defineStore('auth', {
  state: () => {
    return {
      userData: null,
    }
  },
  actions: {
    async checkAuth() {
      return new Promise((resolve, reject) => {
        axios.get('/api/checkAuth')
          .then((response) => {
            this.userData = response.data;
            resolve('');
          })
          .catch((error) => {
            reject(error);
          });
      });
    }
  }
});
