using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using ASPNET.Data;
using ASPNET.Models;

namespace ASPNET.Controllers;

[ApiController]
[Route("api/[controller]")]
public class UsersController(ApplicationDbContext context) : ControllerBase
{
    // GET: api/users/test-connection
    [HttpGet("test-connection")]
    public async Task<IActionResult> TestConnection()
    {
        try
        {
            await context.Database.CanConnectAsync();
            return Ok("Conexión exitosa a la base de datos MySQL");
        }
        catch (Exception ex)
        {
            return BadRequest($"Error de conexión: {ex.Message}");
        }
    }

    // GET: api/users/test-table
    [HttpGet("test-table")]
    public async Task<IActionResult> TestTable()
    {
        try
        {
            var count = await context.Users.CountAsync();
            return Ok($"Tabla ecc_users existe. Total de registros: {count}");
        }
        catch (Exception ex)
        {
            return BadRequest($"Error al acceder a la tabla: {ex.Message}");
        }
    }

    // GET: api/users
    [HttpGet]
    public async Task<ActionResult<IEnumerable<object>>> GetUsers()
    {
        try
        {
            var users = await context.Users
                .AsNoTracking()
                .Select(u => new 
                {
                    Id = u.Id,
                    FirstName = u.FirstName ?? "",
                    Email = u.Email ?? "",
                    Phone = u.Phone ?? "",
                    CreatedAt = u.CreatedAt,
                    UpdatedAt = u.UpdatedAt
                })
                .ToListAsync();
            return Ok(users);
        }
        catch (Exception ex)
        {
            return StatusCode(500, $"Error al obtener usuarios: {ex.Message}");
        }
    }

    // GET: api/users/{id}
    [HttpGet("{id}")]
    public async Task<ActionResult<object>> GetUser(int id)
    {
        try
        {
            var user = await context.Users
                .AsNoTracking()
                .Where(u => u.Id == id)
                .Select(u => new 
                {
                    Id = u.Id,
                    FirstName = u.FirstName ?? "",
                    Email = u.Email ?? "",
                    Phone = u.Phone ?? "",
                    CreatedAt = u.CreatedAt,
                    UpdatedAt = u.UpdatedAt
                })
                .FirstOrDefaultAsync();
            
            if (user == null)
            {
                return NotFound($"Usuario con ID {id} no encontrado");
            }
            
            return Ok(user);
        }
        catch (Exception ex)
        {
            return StatusCode(500, $"Error al obtener usuario: {ex.Message}");
        }
    }
}