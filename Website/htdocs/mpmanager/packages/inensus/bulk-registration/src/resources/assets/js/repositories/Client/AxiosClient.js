import axios from 'axios'

const baseUrl = 'http://mpmanager.local/'

const axiosClient = axios.create(
    {
        baseUrl,
    }
)

axiosClient.interceptors.request.use(
    config => {
        const token = localStorage.getItem('token')
        if (token) {
            config.headers['Authorization'] = 'Bearer ' + token
        }
        return config
    },
    error => {
        Promise.reject(error)
    }
)

export default axiosClient
