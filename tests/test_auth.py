import pytest
import pytest_asyncio
import jwt
from httpx import AsyncClient

from app.auth import Auth
from app.context import AppContext

pytestmark = pytest.mark.asyncio


@pytest_asyncio.fixture
async def auth(context: AppContext) -> Auth:
    context.settings.update_security({
        "admin": {
            "password": "real_password"
        }
    })

    return Auth(context)


async def test_verify_password_correct(auth: Auth) -> None:
    assert auth.verify_password("real_password") is True


async def test_verify_password_incorrect(auth: Auth) -> None:
    assert auth.verify_password("wrong_password") is False


async def test_verify_password_with_no_password_set(context: AppContext, auth: Auth) -> None:
    context.settings.security.admin.password_hash = ""
    assert auth.verify_password("any_password_should_work") is True


async def test_decode_invalid_token(auth: Auth) -> None:
    invalid_token = "this.is.not_a_valid_token"

    with pytest.raises(jwt.InvalidTokenError):
        auth.decode_jwt_token(invalid_token)


async def test_missing_token(client: AsyncClient) -> None:
    response = await client.get("/status")
    assert response.status_code == 401
    result = response.json()
    assert result["code"] == -1
    assert result["message"] == "Invalid token"


async def test_invalid_scheme(client: AsyncClient) -> None:
    response = await client.get("/status", headers={"Authorization": f"Basic maybe-a-token"})
    assert response.status_code == 401
    result = response.json()
    assert result["code"] == -1
    assert result["message"] == "Invalid token"


async def test_invalid_token(client: AsyncClient) -> None:
    response = await client.get("/status", headers={"Authorization": f"Bearer maybe-a-token"})
    assert response.status_code == 401
    result = response.json()
    assert result["code"] == -1
    assert result["message"] == "Invalid token"
