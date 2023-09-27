import CredentialRepository from './CredentialRepository'


const repositories = {
    'credential': CredentialRepository,
}
export default {
    get: name => repositories[name]
}