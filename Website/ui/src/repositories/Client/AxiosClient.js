import axios from 'axios'
import {config} from '@/config'

function  getBaseUrl () {
    if (config.env === 'development') {
        return  `${window.location.protocol}//api.${window.location.hostname}`
    }
    return window.location.protocol + '//' + window.location.hostname
}

export const baseUrl = getBaseUrl()

const axiosClient = axios.create({
        timeout: 120000, // Set the timeout to 120 seconds (adjust as needed)
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
