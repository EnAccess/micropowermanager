import AuthenticationRepository from './AuthenticationRepository'

const repositories = {
    'authentication': AuthenticationRepository,
}
export default {
    get: name => repositories[name]
}